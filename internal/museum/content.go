package museum

import (
	"errors"
	"fmt"
	"os"
	"path/filepath"
	"sort"
	"strings"
	"time"
)

type Entry struct {
	Slug, Title, Summary, Category, Image, Source, Status, Body string
	Tags                                                        []string
	Updated                                                     time.Time
}

type Store struct{ dir string }

func NewStore(dir string) (*Store, error) {
	if err := os.MkdirAll(dir, 0o755); err != nil {
		return nil, err
	}
	return &Store{dir: dir}, nil
}

func (s *Store) All(includeDrafts bool) ([]Entry, error) {
	paths, err := filepath.Glob(filepath.Join(s.dir, "*.md"))
	if err != nil {
		return nil, err
	}
	entries := make([]Entry, 0, len(paths))
	for _, path := range paths {
		entry, err := s.read(path)
		if err != nil {
			return nil, fmt.Errorf("read %s: %w", path, err)
		}
		if includeDrafts || entry.Status == "published" {
			entries = append(entries, entry)
		}
	}
	sort.Slice(entries, func(i, j int) bool { return entries[i].Title < entries[j].Title })
	return entries, nil
}

func (s *Store) Get(slug string) (Entry, error) {
	if !validSlug(slug) {
		return Entry{}, errors.New("invalid exhibit path")
	}
	return s.read(filepath.Join(s.dir, slug+".md"))
}

func (s *Store) Save(entry Entry) error {
	if !validSlug(entry.Slug) {
		return errors.New("use lowercase letters, numbers, and hyphens for the path")
	}
	if strings.TrimSpace(entry.Title) == "" {
		return errors.New("title is required")
	}
	if entry.Status != "published" {
		entry.Status = "draft"
	}
	entry.Updated = time.Now().UTC()
	data := fmt.Sprintf("---\ntitle: %s\nsummary: %s\ncategory: %s\ntags: %s\nimage: %s\nsource: %s\nstatus: %s\nupdated: %s\n---\n\n%s\n",
		sanitizeMeta(entry.Title), sanitizeMeta(entry.Summary), sanitizeMeta(entry.Category), sanitizeMeta(strings.Join(entry.Tags, ", ")),
		sanitizeMeta(entry.Image), sanitizeMeta(entry.Source), entry.Status, entry.Updated.Format(time.RFC3339), strings.TrimSpace(entry.Body))
	return os.WriteFile(filepath.Join(s.dir, entry.Slug+".md"), []byte(data), 0o644)
}

func (s *Store) Delete(slug string) error {
	if !validSlug(slug) {
		return errors.New("invalid exhibit path")
	}
	return os.Remove(filepath.Join(s.dir, slug+".md"))
}

func (s *Store) read(path string) (Entry, error) {
	b, err := os.ReadFile(path)
	if err != nil {
		return Entry{}, err
	}
	e, err := ParseEntry(string(b))
	if err != nil {
		return Entry{}, err
	}
	e.Slug = strings.TrimSuffix(filepath.Base(path), ".md")
	if !validSlug(e.Slug) {
		return Entry{}, errors.New("invalid filename")
	}
	return e, nil
}

func ParseEntry(input string) (Entry, error) {
	parts := strings.SplitN(input, "\n---\n", 2)
	if len(parts) != 2 || !strings.HasPrefix(parts[0], "---\n") {
		return Entry{}, errors.New("expected YAML-style front matter")
	}
	e := Entry{Body: strings.TrimSpace(parts[1]), Status: "draft"}
	for _, line := range strings.Split(strings.TrimPrefix(parts[0], "---\n"), "\n") {
		key, value, ok := strings.Cut(line, ":")
		if !ok {
			continue
		}
		value = strings.TrimSpace(value)
		switch key {
		case "title":
			e.Title = value
		case "summary":
			e.Summary = value
		case "category":
			e.Category = value
		case "tags":
			e.Tags = splitTags(value)
		case "image":
			e.Image = value
		case "source":
			e.Source = value
		case "status":
			e.Status = value
		case "updated":
			e.Updated, _ = time.Parse(time.RFC3339, value)
		}
	}
	if e.Title == "" {
		return Entry{}, errors.New("front matter needs a title")
	}
	return e, nil
}

func splitTags(value string) []string {
	var tags []string
	for _, tag := range strings.Split(value, ",") {
		if tag = strings.TrimSpace(tag); tag != "" {
			tags = append(tags, tag)
		}
	}
	return tags
}

func validSlug(slug string) bool {
	if slug == "" || len(slug) > 90 || strings.HasPrefix(slug, "-") || strings.HasSuffix(slug, "-") {
		return false
	}
	for _, r := range slug {
		if !(r >= 'a' && r <= 'z' || r >= '0' && r <= '9' || r == '-') {
			return false
		}
	}
	return true
}

func sanitizeMeta(value string) string {
	return strings.ReplaceAll(strings.ReplaceAll(strings.TrimSpace(value), "\n", " "), "\r", " ")
}
