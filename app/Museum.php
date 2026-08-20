<?php
declare(strict_types=1);

final class ExhibitStore
{
    public function __construct(private readonly string $directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Cannot create the content directory.');
        }
    }

    /** @return list<array<string, mixed>> */
    public function all(bool $includeDrafts = false): array
    {
        $entries = [];
        foreach (glob($this->directory . '/*.md') ?: [] as $file) {
            $entry = $this->readFile($file);
            if ($includeDrafts || $entry['status'] === 'published') {
                $entries[] = $entry;
            }
        }
        usort($entries, static fn(array $a, array $b): int => strcasecmp($a['title'], $b['title']));
        return $entries;
    }

    /** @return array<string, mixed> */
    public function get(string $slug): array
    {
        $this->assertSlug($slug);
        return $this->readFile($this->directory . '/' . $slug . '.md');
    }

    /** @param array<string, mixed> $entry */
    public function save(array $entry): void
    {
        $this->assertSlug((string) $entry['slug']);
        if (trim((string) $entry['title']) === '') {
            throw new InvalidArgumentException('Title is required.');
        }
        $status = ($entry['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
        $tags = array_filter(array_map('trim', explode(',', (string) ($entry['tags'] ?? ''))));
        $meta = static fn(string $value): string => str_replace(["\r", "\n"], ' ', trim($value));
        $markdown = "---\n";
        $markdown .= 'title: ' . $meta((string) $entry['title']) . "\n";
        $markdown .= 'summary: ' . $meta((string) $entry['summary']) . "\n";
        $markdown .= 'category: ' . $meta((string) $entry['category']) . "\n";
        $markdown .= 'tags: ' . $meta(implode(', ', $tags)) . "\n";
        $markdown .= 'image: ' . $meta((string) $entry['image']) . "\n";
        $markdown .= 'source: ' . $meta((string) $entry['source']) . "\n";
        $markdown .= 'status: ' . $status . "\n";
        $markdown .= 'updated: ' . gmdate(DATE_RFC3339) . "\n---\n\n";
        $markdown .= trim((string) $entry['body']) . "\n";
        if (file_put_contents($this->directory . '/' . $entry['slug'] . '.md', $markdown, LOCK_EX) === false) {
            throw new RuntimeException('Could not write the exhibit file.');
        }
    }

    public function delete(string $slug): void
    {
        $this->assertSlug($slug);
        $path = $this->directory . '/' . $slug . '.md';
        if (!is_file($path) || !unlink($path)) {
            throw new RuntimeException('Could not delete the exhibit file.');
        }
    }

    /** @return array<string, mixed> */
    private function readFile(string $path): array
    {
        $markdown = file_get_contents($path);
        if ($markdown === false || !preg_match('/\A---\R(.*?)\R---\R?(.*)\z/s', $markdown, $parts)) {
            throw new RuntimeException('Invalid Markdown front matter in ' . basename($path) . '.');
        }
        $entry = ['slug' => basename($path, '.md'), 'title' => '', 'summary' => '', 'category' => '', 'tags' => [], 'image' => '', 'source' => '', 'status' => 'draft', 'updated' => '', 'body' => trim($parts[2])];
        foreach (preg_split('/\R/', $parts[1]) ?: [] as $line) {
            [$key, $value] = array_pad(explode(':', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);
            if ($key === 'tags') {
                $entry['tags'] = array_values(array_filter(array_map('trim', explode(',', $value))));
            } elseif (array_key_exists($key, $entry)) {
                $entry[$key] = $value;
            }
        }
        if ($entry['title'] === '') {
            throw new RuntimeException('Exhibit has no title: ' . basename($path) . '.');
        }
        return $entry;
    }

    private function assertSlug(string $slug): void
    {
        if (!preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug) || strlen($slug) > 90) {
            throw new InvalidArgumentException('Use lowercase letters, numbers, and hyphens for the path.');
        }
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function safe_url(string $url): bool
{
    $parts = parse_url($url);
    return $parts !== false && (!isset($parts['scheme']) || in_array(strtolower($parts['scheme']), ['https', 'http', 'mailto'], true));
}

function render_markdown(string $markdown): string
{
    $lines = preg_split('/\R/', $markdown) ?: [];
    $out = [];
    $inCode = false;
    $inList = false;
    $closeList = static function () use (&$out, &$inList): void {
        if ($inList) { $out[] = '</ul>'; $inList = false; }
    };
    foreach ($lines as $raw) {
        $line = trim($raw);
        if (str_starts_with($line, '```')) {
            $closeList();
            $out[] = $inCode ? '</code></pre>' : '<pre><code>';
            $inCode = !$inCode;
            continue;
        }
        if ($inCode) { $out[] = h($raw) . "\n"; continue; }
        if ($line === '') { $closeList(); continue; }
        if (str_starts_with($line, '### ')) { $closeList(); $out[] = '<h3>' . render_inline(substr($line, 4)) . '</h3>'; continue; }
        if (str_starts_with($line, '## ')) { $closeList(); $out[] = '<h2>' . render_inline(substr($line, 3)) . '</h2>'; continue; }
        if (str_starts_with($line, '# ')) { $closeList(); $out[] = '<h1>' . render_inline(substr($line, 2)) . '</h1>'; continue; }
        if (str_starts_with($line, '- ')) {
            if (!$inList) { $out[] = '<ul>'; $inList = true; }
            $out[] = '<li>' . render_inline(substr($line, 2)) . '</li>';
            continue;
        }
        $closeList();
        $out[] = '<p>' . render_inline($line) . '</p>';
    }
    $closeList();
    if ($inCode) { $out[] = '</code></pre>'; }
    return implode("\n", $out);
}

function render_inline(string $text): string
{
    $text = h($text);
    $text = preg_replace_callback('/\[([^\]]+)\]\(([^\s)]+)\)/', static function (array $match): string {
        $url = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return safe_url($url) ? '<a href="' . h($url) . '" rel="noopener noreferrer">' . $match[1] . '</a>' : $match[1];
    }, $text) ?? $text;
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text) ?? $text;
    $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text) ?? $text;
    return preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text) ?? $text;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

function entry_from_request(): array
{
    return [
        'slug' => trim((string) ($_POST['slug'] ?? '')), 'title' => trim((string) ($_POST['title'] ?? '')),
        'summary' => trim((string) ($_POST['summary'] ?? '')), 'category' => trim((string) ($_POST['category'] ?? '')),
        'tags' => trim((string) ($_POST['tags'] ?? '')), 'image' => trim((string) ($_POST['image'] ?? '')),
        'source' => trim((string) ($_POST['source'] ?? '')), 'status' => (string) ($_POST['status'] ?? 'draft'),
        'body' => (string) ($_POST['body'] ?? ''),
    ];
}
