package museum

import (
	"net/http"
	"net/http/httptest"
	"net/url"
	"strings"
	"testing"
)

func TestAuthenticatedCuratorCanPublishAnExhibit(t *testing.T) {
	content := t.TempDir()
	app, err := New(Config{ContentDir: content, StaticDir: "../../static", Password: "test-password", Secret: "test-secret"})
	if err != nil {
		t.Fatal(err)
	}
	h := app.Handler()

	login := httptest.NewRequest(http.MethodPost, "/admin/login", strings.NewReader(url.Values{"password": {"test-password"}}.Encode()))
	login.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	loginResult := httptest.NewRecorder()
	h.ServeHTTP(loginResult, login)
	if loginResult.Code != http.StatusSeeOther {
		t.Fatalf("login status = %d", loginResult.Code)
	}
	cookies := loginResult.Result().Cookies()
	if len(cookies) != 1 {
		t.Fatalf("wanted one session cookie, got %d", len(cookies))
	}

	form := url.Values{
		"slug":     {"test-exhibit"},
		"title":    {"Test exhibit"},
		"summary":  {"A test entry."},
		"category": {"Tests"},
		"status":   {"published"},
		"body":     {"## Ready\n\nThis is **published**."},
	}
	save := httptest.NewRequest(http.MethodPost, "/admin/exhibits/new", strings.NewReader(form.Encode()))
	save.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	save.AddCookie(cookies[0])
	form.Set("csrf", app.csrf(save))
	save.Body = ioNopCloser{strings.NewReader(form.Encode())}
	save.ContentLength = int64(len(form.Encode()))
	saved := httptest.NewRecorder()
	h.ServeHTTP(saved, save)
	if saved.Code != http.StatusSeeOther {
		t.Fatalf("save status = %d: %s", saved.Code, saved.Body.String())
	}

	public := httptest.NewRecorder()
	h.ServeHTTP(public, httptest.NewRequest(http.MethodGet, "/exhibits/test-exhibit", nil))
	if public.Code != http.StatusOK || !strings.Contains(public.Body.String(), "This is <strong>published</strong>") {
		t.Fatalf("public exhibit not rendered: %d %s", public.Code, public.Body.String())
	}
}

type ioNopCloser struct{ *strings.Reader }

func (ioNopCloser) Close() error { return nil }
