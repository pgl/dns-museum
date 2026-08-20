package museum

import (
	"crypto/hmac"
	"crypto/rand"
	"crypto/sha256"
	"embed"
	"encoding/base64"
	"fmt"
	"html/template"
	"net/http"
	"sort"
	"strings"
	"time"
)

//go:embed templates/*.html
var templateFiles embed.FS

type Config struct {
	ContentDir, StaticDir, Password, Secret string
	Secure                                  bool
}
type App struct {
	store     *Store
	config    Config
	templates map[string]*template.Template
}
type pageData struct {
	Title      string
	Entry      Entry
	Entries    []Entry
	Categories []string
	Admin      bool
	CSRF       string
	Error      string
	Preview    template.HTML
}

func New(config Config) (*App, error) {
	store, err := NewStore(config.ContentDir)
	if err != nil {
		return nil, err
	}
	if config.Secret == "" {
		config.Secret = randomSecret()
	}
	funcs := template.FuncMap{"renderMarkdown": func(s string) template.HTML { return template.HTML(RenderMarkdown(s)) }}
	base, err := templateFiles.ReadFile("templates/base.html")
	if err != nil {
		return nil, err
	}
	pages := []string{"home.html", "exhibit.html", "login.html", "dashboard.html", "edit.html"}
	templates := make(map[string]*template.Template, len(pages))
	for _, page := range pages {
		pageBytes, err := templateFiles.ReadFile("templates/" + page)
		if err != nil {
			return nil, err
		}
		t, err := template.New("base.html").Funcs(funcs).Parse(string(base))
		if err != nil {
			return nil, err
		}
		if _, err = t.Parse(string(pageBytes)); err != nil {
			return nil, err
		}
		templates[page] = t
	}
	return &App{store: store, config: config, templates: templates}, nil
}

func (a *App) Handler() http.Handler {
	mux := http.NewServeMux()
	mux.Handle("GET /static/", http.StripPrefix("/static/", http.FileServer(http.Dir(a.config.StaticDir))))
	mux.HandleFunc("GET /", a.home)
	mux.HandleFunc("GET /exhibits/{slug}", a.exhibit)
	mux.HandleFunc("GET /admin/login", a.loginForm)
	mux.HandleFunc("POST /admin/login", a.login)
	mux.HandleFunc("POST /admin/logout", a.logout)
	mux.HandleFunc("GET /admin", a.requireAdmin(a.dashboard))
	mux.HandleFunc("GET /admin/exhibits/new", a.requireAdmin(a.newEntry))
	mux.HandleFunc("POST /admin/exhibits/new", a.requireAdmin(a.saveNew))
	mux.HandleFunc("POST /admin/exhibits/new/preview", a.requireAdmin(a.previewEntry))
	mux.HandleFunc("GET /admin/exhibits/{slug}", a.requireAdmin(a.editEntry))
	mux.HandleFunc("POST /admin/exhibits/{slug}", a.requireAdmin(a.saveEntry))
	mux.HandleFunc("POST /admin/exhibits/{slug}/preview", a.requireAdmin(a.previewEntry))
	mux.HandleFunc("POST /admin/exhibits/{slug}/delete", a.requireAdmin(a.deleteEntry))
	return securityHeaders(mux)
}

func (a *App) home(w http.ResponseWriter, r *http.Request) {
	entries, err := a.store.All(false)
	if err != nil {
		a.serverError(w, err)
		return
	}
	a.render(w, "home.html", pageData{Title: "Museum of DNS", Entries: entries, Categories: categories(entries)})
}

func (a *App) exhibit(w http.ResponseWriter, r *http.Request) {
	e, err := a.store.Get(r.PathValue("slug"))
	if err != nil || e.Status != "published" {
		http.NotFound(w, r)
		return
	}
	a.render(w, "exhibit.html", pageData{Title: e.Title + " · Museum of DNS", Entry: e})
}

func (a *App) loginForm(w http.ResponseWriter, r *http.Request) {
	a.render(w, "login.html", pageData{Title: "Curator sign in"})
}
func (a *App) login(w http.ResponseWriter, r *http.Request) {
	if a.config.Password == "" || !secureEqual(r.FormValue("password"), a.config.Password) {
		a.renderStatus(w, "login.html", pageData{Title: "Curator sign in", Error: "Sign in was not accepted."}, http.StatusUnauthorized)
		return
	}
	http.SetCookie(w, a.sessionCookie())
	http.Redirect(w, r, "/admin", http.StatusSeeOther)
}
func (a *App) logout(w http.ResponseWriter, r *http.Request) {
	http.SetCookie(w, &http.Cookie{Name: "museum_session", Value: "", Path: "/", MaxAge: -1})
	http.Redirect(w, r, "/", http.StatusSeeOther)
}
func (a *App) dashboard(w http.ResponseWriter, r *http.Request) {
	entries, err := a.store.All(true)
	if err != nil {
		a.serverError(w, err)
		return
	}
	a.render(w, "dashboard.html", pageData{Title: "Curator desk", Entries: entries, Admin: true, CSRF: a.csrf(r)})
}
func (a *App) newEntry(w http.ResponseWriter, r *http.Request) {
	a.render(w, "edit.html", pageData{Title: "New exhibit", Entry: Entry{Status: "draft"}, Admin: true, CSRF: a.csrf(r)})
}
func (a *App) saveNew(w http.ResponseWriter, r *http.Request) { a.save(w, r, "") }
func (a *App) editEntry(w http.ResponseWriter, r *http.Request) {
	e, err := a.store.Get(r.PathValue("slug"))
	if err != nil {
		http.NotFound(w, r)
		return
	}
	a.render(w, "edit.html", pageData{Title: "Edit " + e.Title, Entry: e, Admin: true, CSRF: a.csrf(r)})
}
func (a *App) saveEntry(w http.ResponseWriter, r *http.Request) { a.save(w, r, r.PathValue("slug")) }

func (a *App) save(w http.ResponseWriter, r *http.Request, currentSlug string) {
	if !a.validCSRF(r) {
		http.Error(w, "Request expired. Reload and try again.", http.StatusForbidden)
		return
	}
	e := entryFromForm(r)
	if currentSlug != "" && e.Slug != currentSlug {
		a.renderStatus(w, "edit.html", pageData{Title: "Edit exhibit", Entry: e, Admin: true, CSRF: a.csrf(r), Error: "The path cannot change after creation."}, http.StatusBadRequest)
		return
	}
	if err := a.store.Save(e); err != nil {
		a.renderStatus(w, "edit.html", pageData{Title: "Edit exhibit", Entry: e, Admin: true, CSRF: a.csrf(r), Error: err.Error()}, http.StatusBadRequest)
		return
	}
	http.Redirect(w, r, "/admin/exhibits/"+e.Slug, http.StatusSeeOther)
}
func (a *App) previewEntry(w http.ResponseWriter, r *http.Request) {
	if !a.validCSRF(r) {
		http.Error(w, "Request expired. Reload and try again.", 403)
		return
	}
	e := entryFromForm(r)
	a.render(w, "edit.html", pageData{Title: "Preview " + e.Title, Entry: e, Admin: true, CSRF: a.csrf(r), Preview: template.HTML(RenderMarkdown(e.Body))})
}
func (a *App) deleteEntry(w http.ResponseWriter, r *http.Request) {
	if !a.validCSRF(r) {
		http.Error(w, "Request expired. Reload and try again.", 403)
		return
	}
	if err := a.store.Delete(r.PathValue("slug")); err != nil {
		http.NotFound(w, r)
		return
	}
	http.Redirect(w, r, "/admin", http.StatusSeeOther)
}

func (a *App) requireAdmin(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		if !a.authorized(r) {
			http.Redirect(w, r, "/admin/login", http.StatusSeeOther)
			return
		}
		next(w, r)
	}
}
func (a *App) authorized(r *http.Request) bool {
	c, err := r.Cookie("museum_session")
	if err != nil {
		return false
	}
	parts := strings.Split(c.Value, ".")
	if len(parts) != 2 {
		return false
	}
	raw, err := base64.RawURLEncoding.DecodeString(parts[0])
	if err != nil {
		return false
	}
	expiry, err := time.Parse(time.RFC3339, string(raw))
	if err != nil || time.Now().After(expiry) {
		return false
	}
	return hmac.Equal([]byte(parts[1]), []byte(a.sign(parts[0])))
}
func (a *App) sessionCookie() *http.Cookie {
	expiry := time.Now().UTC().Add(12 * time.Hour).Format(time.RFC3339)
	value := base64.RawURLEncoding.EncodeToString([]byte(expiry))
	return &http.Cookie{Name: "museum_session", Value: value + "." + a.sign(value), Path: "/", HttpOnly: true, Secure: a.config.Secure, SameSite: http.SameSiteLaxMode, Expires: time.Now().Add(12 * time.Hour)}
}
func (a *App) csrf(r *http.Request) string {
	c, _ := r.Cookie("museum_session")
	return a.sign("csrf:" + c.Value)
}
func (a *App) validCSRF(r *http.Request) bool {
	return a.authorized(r) && hmac.Equal([]byte(r.FormValue("csrf")), []byte(a.csrf(r)))
}
func (a *App) sign(value string) string {
	h := hmac.New(sha256.New, []byte(a.config.Secret))
	_, _ = h.Write([]byte(value))
	return base64.RawURLEncoding.EncodeToString(h.Sum(nil))
}
func randomSecret() string {
	b := make([]byte, 32)
	if _, err := rand.Read(b); err != nil {
		panic(err)
	}
	return base64.RawURLEncoding.EncodeToString(b)
}
func secureEqual(aValue, bValue string) bool { return hmac.Equal([]byte(aValue), []byte(bValue)) }
func entryFromForm(r *http.Request) Entry {
	return Entry{Slug: strings.TrimSpace(r.FormValue("slug")), Title: strings.TrimSpace(r.FormValue("title")), Summary: strings.TrimSpace(r.FormValue("summary")), Category: strings.TrimSpace(r.FormValue("category")), Tags: splitTags(r.FormValue("tags")), Image: strings.TrimSpace(r.FormValue("image")), Source: strings.TrimSpace(r.FormValue("source")), Status: r.FormValue("status"), Body: r.FormValue("body")}
}
func categories(entries []Entry) []string {
	m := map[string]bool{}
	for _, e := range entries {
		if e.Category != "" {
			m[e.Category] = true
		}
	}
	v := make([]string, 0, len(m))
	for c := range m {
		v = append(v, c)
	}
	sort.Strings(v)
	return v
}
func (a *App) render(w http.ResponseWriter, name string, data pageData) {
	a.renderStatus(w, name, data, http.StatusOK)
}
func (a *App) renderStatus(w http.ResponseWriter, name string, data pageData, status int) {
	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	w.WriteHeader(status)
	if err := a.templates[name].ExecuteTemplate(w, name, data); err != nil {
		fmt.Println(err)
	}
}
func (a *App) serverError(w http.ResponseWriter, err error) {
	http.Error(w, "The museum catalogue is temporarily unavailable.", http.StatusInternalServerError)
	fmt.Println(err)
}
func securityHeaders(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("X-Content-Type-Options", "nosniff")
		w.Header().Set("X-Frame-Options", "DENY")
		w.Header().Set("Referrer-Policy", "strict-origin-when-cross-origin")
		w.Header().Set("Content-Security-Policy", "default-src 'self'; img-src 'self' https:; style-src 'self'; script-src 'self'; base-uri 'self'; form-action 'self'")
		next.ServeHTTP(w, r)
	})
}
