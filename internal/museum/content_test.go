package museum

import (
	"strings"
	"testing"
)

func TestParseEntry(t *testing.T) {
	entry, err := ParseEntry("---\ntitle: DNS art\nsummary: A route\ncategory: Traceroutes\ntags: art, routing\nstatus: published\n---\n\n## Notes\n\nA strange route.")
	if err != nil {
		t.Fatal(err)
	}
	if entry.Title != "DNS art" || entry.Status != "published" || len(entry.Tags) != 2 {
		t.Fatalf("unexpected entry: %#v", entry)
	}
}

func TestSlugValidation(t *testing.T) {
	for _, slug := range []string{"html-over-dns", "dnsfs", "ip-over-dns-2"} {
		if !validSlug(slug) {
			t.Errorf("expected valid: %s", slug)
		}
	}
	for _, slug := range []string{"../escape", "Two Words", "-start", "end-"} {
		if validSlug(slug) {
			t.Errorf("expected invalid: %s", slug)
		}
	}
}

func TestRenderMarkdownEscapesHTMLAndUnsafeLinks(t *testing.T) {
	html := RenderMarkdown("# Safe\n\n<script>alert(1)</script> [bad](javascript:alert(1)) [good](https://example.com)")
	if strings.Contains(html, "<script>") || strings.Contains(html, "javascript:") {
		t.Fatalf("unsafe output: %s", html)
	}
	if !strings.Contains(html, `href="https://example.com"`) {
		t.Fatalf("safe link missing: %s", html)
	}
}
