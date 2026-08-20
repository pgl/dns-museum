package museum

import (
	"html"
	"net/url"
	"regexp"
	"strings"
)

var linkPattern = regexp.MustCompile(`\[([^\]]+)\]\(([^\s)]+)\)`)

// RenderMarkdown deliberately supports a compact, safe Markdown subset.
// Raw HTML is escaped so contributor content cannot inject scripts into the museum.
func RenderMarkdown(input string) string {
	lines := strings.Split(strings.ReplaceAll(input, "\r\n", "\n"), "\n")
	var out []string
	inCode, inList := false, false
	closeList := func() {
		if inList {
			out = append(out, "</ul>")
			inList = false
		}
	}
	for _, raw := range lines {
		line := strings.TrimSpace(raw)
		if strings.HasPrefix(line, "```") {
			closeList()
			if inCode {
				out = append(out, "</code></pre>")
			} else {
				out = append(out, "<pre><code>")
			}
			inCode = !inCode
			continue
		}
		if inCode {
			out = append(out, html.EscapeString(raw)+"\n")
			continue
		}
		if line == "" {
			closeList()
			continue
		}
		if strings.HasPrefix(line, "### ") {
			closeList()
			out = append(out, "<h3>"+inline(line[4:])+"</h3>")
			continue
		}
		if strings.HasPrefix(line, "## ") {
			closeList()
			out = append(out, "<h2>"+inline(line[3:])+"</h2>")
			continue
		}
		if strings.HasPrefix(line, "# ") {
			closeList()
			out = append(out, "<h1>"+inline(line[2:])+"</h1>")
			continue
		}
		if strings.HasPrefix(line, "- ") {
			if !inList {
				out = append(out, "<ul>")
				inList = true
			}
			out = append(out, "<li>"+inline(line[2:])+"</li>")
			continue
		}
		closeList()
		out = append(out, "<p>"+inline(line)+"</p>")
	}
	closeList()
	if inCode {
		out = append(out, "</code></pre>")
	}
	return strings.Join(out, "\n")
}

func inline(value string) string {
	escaped := html.EscapeString(value)
	escaped = linkPattern.ReplaceAllStringFunc(escaped, func(match string) string {
		parts := linkPattern.FindStringSubmatch(match)
		href := html.UnescapeString(parts[2])
		if !safeHref(href) {
			return parts[1]
		}
		return `<a href="` + html.EscapeString(href) + `" rel="noopener noreferrer">` + parts[1] + `</a>`
	})
	escaped = regexp.MustCompile("`([^`]+)`").ReplaceAllString(escaped, "<code>$1</code>")
	escaped = regexp.MustCompile(`\*\*([^*]+)\*\*`).ReplaceAllString(escaped, "<strong>$1</strong>")
	escaped = regexp.MustCompile(`\*([^*]+)\*`).ReplaceAllString(escaped, "<em>$1</em>")
	return escaped
}

func safeHref(raw string) bool {
	u, err := url.Parse(raw)
	if err != nil {
		return false
	}
	return u.Scheme == "" || u.Scheme == "https" || u.Scheme == "http" || u.Scheme == "mailto"
}
