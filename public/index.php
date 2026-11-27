<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Museum.php';

function config_value(string $name, string $default = ''): string {
    static $values = null;
    if ($values === null) {
        $envFile = dirname(__DIR__) . '/.env';
        $values = is_file($envFile) ? (parse_ini_file($envFile, false, INI_SCANNER_RAW) ?: []) : [];
    }
    $value = getenv($name);
    return $value !== false ? $value : (string) ($values[$name] ?? $default);
}

$secureCookies = config_value('MUSEUM_SECURE_COOKIES') === 'true';
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $secureCookies, 'path' => '/']);
$store = new ExhibitStore(dirname(__DIR__) . '/content/exhibits');
$password = config_value('MUSEUM_ADMIN_PASSWORD');
$passwordConfigured = $password !== '' && $password !== 'replace-with-a-long-unique-password';
$passwordFingerprint = hash('sha256', $passwordConfigured ? $password : 'disabled');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$hasSessionCookie = isset($_COOKIE[session_name()]);
$isAdminPath = str_starts_with($path, '/admin');
$hasSession = $hasSessionCookie;
if ($hasSession) { session_start(); }
if (!empty($_SESSION['museum_admin']) && (!$passwordConfigured || !hash_equals((string) ($_SESSION['museum_password_fingerprint'] ?? ''), $passwordFingerprint))) {
    $_SESSION = [];
    session_regenerate_id(true);
}

$isPublicRoute = $path === '/' || $path === '/categories' || $path === '/categories/' || $path === '/about' || $path === '/about/' || $path === '/sitemap.xml' || preg_match('#\A/exhibits/[a-z0-9-]+\z#', $path) === 1;
if ($hasSession || $isAdminPath || !$isPublicRoute || !in_array($method, ['GET', 'HEAD'], true)) {
    header('Cache-Control: private, no-store');
} else {
    header('Cache-Control: public, max-age=60');
    header('Vary: Cookie');
}

header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' https: blob:; style-src 'self'; script-src 'self'; base-uri 'self'; form-action 'self'");

function layout(string $title, string $content, string $description = 'The DNS Museum is a field guide to unusual uses of the Domain Name System.'): void {
    global $path;
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="' . h($description) . '"><title>' . h($title) . '</title><link rel="icon" type="image/svg+xml" href="/static/images/favicon.svg"><link rel="stylesheet" href="/static/css/site.css?v=' . (string) filemtime(__DIR__ . '/static/css/site.css') . '"></head><body>';
    echo '<a class="skip-link" href="#content">Skip to content</a><header class="site-header"><a class="wordmark" href="/"><img class="museum-logo" src="/static/images/museum-logo.png" alt="" width="40" height="40"><span>The</span><strong>DNS</strong><span>Museum</span></a><nav class="site-nav" aria-label="Main navigation"><a href="/#collection"' . ($path === '/' ? ' aria-current="page"' : '') . '>Collection</a><a href="/categories/"' . ($path === '/categories/' ? ' aria-current="page"' : '') . '>Categories</a><a href="/about/"' . ($path === '/about/' ? ' aria-current="page"' : '') . '>About</a><button type="button" class="quick-search-trigger" data-search-open aria-haspopup="dialog" aria-keyshortcuts="Meta+K Control+K"><span class="search-icon" aria-hidden="true"></span>Search <kbd>⌘ K</kbd></button></nav></header><main id="content">' . $content . '</main>';
    echo '<dialog class="quick-search" data-quick-search aria-labelledby="quick-search-title"><div class="quick-search-heading"><h2 id="quick-search-title">Search exhibits</h2><button type="button" data-search-close aria-label="Close search">×</button></div><label for="quick-search-input">Search all exhibits</label><input id="quick-search-input" type="search" placeholder="Search titles, topics, and text" autocomplete="off" aria-describedby="quick-search-help"><small id="quick-search-help">Press Tab to complete a matching title.</small><p class="quick-search-status" role="status" aria-live="polite"></p><ul class="quick-search-results">';
    echo '</ul></dialog><footer><p>A field guide to unusual uses of DNS. Original content by Peter Lowe is licensed under <a href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a>; third-party material is excluded. <span class="footer-separator" aria-hidden="true">•</span> <a href="/about/">About</a> <span class="footer-separator" aria-hidden="true">•</span> <a href="https://github.com/pgl/dns-museum">GitHub</a></p></footer><script src="/static/js/museum.js?v=' . (string) filemtime(__DIR__ . '/static/js/museum.js') . '" defer></script>';
    if ($path === '/admin' || str_starts_with($path, '/admin/')) {
        echo '<script src="/static/js/admin.js?v=' . (string) filemtime(__DIR__ . '/static/js/admin.js') . '" defer></script>';
    }
    echo '</body></html>';
}

function redirect(string $to): never { header('Location: ' . $to, true, 303); exit; }
function require_admin(): void { if (empty($_SESSION['museum_admin'])) { redirect('/admin/login'); } }
function login_attempts(string $key, bool $recordFailure = false, bool $clear = false): array {
    $directory = dirname(__DIR__) . '/content/.state';
    if (!is_dir($directory) && !@mkdir($directory, 0700) && !is_dir($directory)) { return ['allowed' => false, 'retry_after' => 900]; }
    $directoryStat = @lstat($directory);
    if ($directoryStat === false || (($directoryStat['mode'] & 0170000) !== 0040000) || is_link($directory) || (($directoryStat['mode'] & 0077) !== 0) || !function_exists('posix_geteuid') || fileowner($directory) !== posix_geteuid()) { return ['allowed' => false, 'retry_after' => 900]; }
    $file = $directory . DIRECTORY_SEPARATOR . 'attempts.json';
    $fileStat = @lstat($file);
    if ($fileStat !== false && ((($fileStat['mode'] & 0170000) !== 0100000) || (($fileStat['mode'] & 0077) !== 0) || is_link($file) || fileowner($file) !== posix_geteuid())) { return ['allowed' => false, 'retry_after' => 900]; }
    $handle = @fopen($file, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) { if (is_resource($handle)) { fclose($handle); } return ['allowed' => false, 'retry_after' => 900]; }
    @chmod($file, 0600);
    rewind($handle);
    $data = json_decode((string) stream_get_contents($handle), true);
    if (!is_array($data)) { $data = []; }
    $now = time();
    $attempts = array_values(array_filter($data[$key] ?? [], static fn ($time): bool => is_int($time) && $time > $now - 900));
    $allowed = count($attempts) < 5;
    if ($clear) { $attempts = []; }
    elseif ($recordFailure && $allowed) { $attempts[] = $now; }
    $data[$key] = $attempts;
    foreach ($data as $client => $times) {
        $data[$client] = is_array($times) ? array_values(array_filter($times, static fn ($time): bool => is_int($time) && $time > $now - 900)) : [];
        if ($data[$client] === []) { unset($data[$client]); }
    }
    rewind($handle);
    $encoded = json_encode($data, JSON_THROW_ON_ERROR);
    $saved = ftruncate($handle, 0) && fwrite($handle, $encoded) === strlen($encoded) && fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return ['allowed' => $saved && $allowed, 'retry_after' => !$saved || $attempts === [] ? 900 : max(1, 900 - ($now - min($attempts)))];
}
function exhibit_edit_link(array $entry): string { return empty($_SESSION['museum_admin']) ? '' : '<a class="edit-page-link" href="/admin/exhibits/' . rawurlencode((string) $entry['slug']) . '">Edit</a>'; }
function form_value(array $entry, string $key): string { return h((string) ($entry[$key] ?? '')); }
function collection_sections(array $entries): array {
    $labels = ['Traceroutes' => 'Traceroutes', 'DNS over something else' => 'DNS over...', 'Tools and toys' => 'Tools and toys', 'Tunnelling' => 'Tunnelling', 'Other things' => 'Other things'];
    $sections = array_fill_keys(array_keys($labels), []);
    foreach ($entries as $entry) {
        if (!array_key_exists($entry['category'], $sections)) { $labels[$entry['category']] = $entry['category']; $sections[$entry['category']] = []; }
        $sections[$entry['category']][] = $entry;
    }
    return [$labels, $sections];
}
function section_anchor(string $category): string { return strtolower(str_replace(' ', '-', str_replace(' something else', '', $category))); }
function thumbnail_image(string $image): string {
    $slash = strrpos($image, '/');
    $name = pathinfo($image, PATHINFO_FILENAME) . '.webp';
    return $slash === false ? $name : substr($image, 0, $slash + 1) . 'thumbnails/' . $name;
}
const UPLOAD_URL = '/static/images/uploads/';
const UPLOAD_DIRECTORY = __DIR__ . '/../content/uploads';

function serve_uploaded_image(ExhibitStore $store, string $path, string $method): never {
    header('Cache-Control: private, no-store');
    if (!in_array($method, ['GET', 'HEAD'], true)
        || !preg_match('#\A(?:body/|thumbnails/)?[a-z0-9-]+\.(jpg|png|webp)\z#', substr($path, strlen(UPLOAD_URL)), $matches)) {
        http_response_code(404);
        exit('Not found');
    }
    $file = UPLOAD_DIRECTORY . '/' . substr($path, strlen(UPLOAD_URL));
    if (!is_file($file)) {
        http_response_code(404);
        exit('Not found');
    }
    $permitted = !empty($_SESSION['museum_admin']);
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
    if (!$permitted) {
        foreach ($store->all() as $entry) {
            if ($path === $entry['image'] || $path === thumbnail_image($entry['image'])
                || str_contains(render_markdown($entry['body']), ' href="' . $path . '"')) {
                $permitted = true;
                break;
            }
        }
    }
    if (!$permitted) {
        http_response_code(404);
        exit('Not found');
    }
    $stream = @fopen($file, 'rb');
    if ($stream === false) {
        http_response_code(404);
        exit('Not found');
    }
    $types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    header('Content-Type: ' . $types[$matches[1]]);
    header('Content-Length: ' . fstat($stream)['size']);
    if ($method === 'GET') { fpassthru($stream); }
    fclose($stream);
    exit;
}

function store_uploaded_image(string $slug, array &$newFiles): string {
    if (!preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug) || strlen($slug) > 90) { throw new InvalidArgumentException('Use a valid entry path before uploading an image.'); }
    if (!isset($_FILES['image_upload']) || !is_array($_FILES['image_upload']) || ($_FILES['image_upload']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { return ''; }
    $upload = $_FILES['image_upload'];
    if (($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $upload['tmp_name']) || (int) $upload['size'] > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('Choose an image smaller than 2 MB and try again.');
    }
    $info = @getimagesize((string) $upload['tmp_name']);
    if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) || $info[0] > 8000 || $info[1] > 8000 || $info[0] * $info[1] > 20000000) {
        throw new InvalidArgumentException('Use a JPEG, PNG, or WebP image up to 8000 pixels wide and high.');
    }
    $directory = UPLOAD_DIRECTORY;
    $thumbDirectory = $directory . '/thumbnails';
    foreach ([$directory, $thumbDirectory] as $path) {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) { throw new RuntimeException('The image directory could not be created.'); }
    }
    $extension = match ($info[2]) { IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp' };
    $convert = config_value('MUSEUM_IMAGE_MAGICK', '/usr/bin/convert');
    if (!is_executable($convert) || !function_exists('proc_open')) { throw new RuntimeException('ImageMagick is not available to create the thumbnail.'); }
    $name = $slug . '-' . bin2hex(random_bytes(16));
    $original = $directory . '/' . $name . '.' . $extension;
    $thumbnail = $thumbDirectory . '/' . $name . '.webp';
    if (!move_uploaded_file((string) $upload['tmp_name'], $original)) { throw new RuntimeException('The uploaded image could not be saved.'); }
    $newFiles[] = $original;
    $newFiles[] = $thumbnail;
    $process = proc_open([$convert, $original, '-auto-orient', '-thumbnail', '480x480>', '-strip', '-quality', '72', '-define', 'webp:method=6', 'webp:' . $thumbnail], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    $status = is_resource($process) ? proc_close($process) : -1;
    $thumbnailInfo = $status === 0 ? @getimagesize($thumbnail) : false;
    if ($thumbnailInfo === false || $thumbnailInfo[2] !== IMAGETYPE_WEBP || max($thumbnailInfo[0], $thumbnailInfo[1]) > 480) {
        throw new RuntimeException('The thumbnail could not be created. Check that ImageMagick supports WebP.');
    }
    return UPLOAD_URL . $name . '.' . $extension;
}

function store_body_images(string $slug, string $body, array &$newFiles): string {
    $uploads = $_FILES['body_images'] ?? null;
    if (!is_array($uploads) || !is_array($uploads['tmp_name'] ?? null)) { return $body; }
    if (!preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug) || strlen($slug) > 90) { throw new InvalidArgumentException('Use a valid entry path before uploading an image.'); }
    $directory = UPLOAD_DIRECTORY . '/body';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) { throw new RuntimeException('The body image directory could not be created.'); }
    $paths = [];
    foreach ($uploads['tmp_name'] as $index => $temporaryPath) {
        $error = (int) ($uploads['error'][$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) { continue; }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) $temporaryPath) || (int) ($uploads['size'][$index] ?? 0) > 2 * 1024 * 1024) {
            throw new InvalidArgumentException('Choose body screenshots smaller than 2 MB each and try again.');
        }
        $info = @getimagesize((string) $temporaryPath);
        if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) || $info[0] > 8000 || $info[1] > 8000 || $info[0] * $info[1] > 20000000) {
            throw new InvalidArgumentException('Use JPEG, PNG, or WebP screenshots up to 8000 pixels wide and high.');
        }
        $extension = match ($info[2]) { IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp' };
        $filename = $slug . '-' . bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file((string) $temporaryPath, $directory . '/' . $filename)) { throw new RuntimeException('A body screenshot could not be saved.'); }
        $newFiles[] = $directory . '/' . $filename;
        $paths[(int) $index] = UPLOAD_URL . 'body/' . $filename;
    }
    return preg_replace_callback('/!\[([^\]]*)\]\(upload:(\d+)\)(?:\{width=(25|50|75|100)%\})?/', static function (array $match) use ($paths): string {
        if (!isset($paths[(int) $match[2]])) { return $match[0]; }
        $width = in_array($match[3] ?? '', ['25', '50', '75', '100'], true) ? $match[3] : '50';
        return '![' . $match[1] . '](' . $paths[(int) $match[2]] . '){width=' . $width . '%}';
    }, $body) ?? $body;
}

function save_exhibit_with_uploads(ExhibitStore $store, array $entry): void {
    $newFiles = [];
    try {
        $cover = store_uploaded_image($entry['slug'], $newFiles);
        if ($cover !== '') { $entry['image'] = $cover; }
        $entry['body'] = store_body_images($entry['slug'], $entry['body'], $newFiles);
        $store->save($entry);
    } catch (Throwable $error) {
        foreach ($newFiles as $file) {
            if (is_file($file) && !@unlink($file)) { error_log('Could not clean up an exhibit upload.'); }
        }
        throw $error;
    }
}

function editor(array $entry, string $error = '', string $preview = ''): void {
    global $store;
    $isExisting = $entry['slug'] !== '';
    $allEntries = $store->all(true);
    $categories = array_values(array_unique(array_filter(array_map(static fn (array $item): string => trim((string) $item['category']), $allEntries))));
    sort($categories, SORT_NATURAL | SORT_FLAG_CASE);
    $tagMap = [];
    foreach ($allEntries as $item) {
        foreach (($item['tags'] ?? []) as $tag) {
            $tag = trim((string) $tag);
            if ($tag !== '') { $tagMap[strtolower($tag)] = $tag; }
        }
    }
    $existingTags = array_values($tagMap);
    sort($existingTags, SORT_NATURAL | SORT_FLAG_CASE);
    $category = trim((string) ($entry['category'] ?? ''));
    $newCategory = !in_array($category, $categories, true) && $category !== '';
    $tags = is_array($entry['tags'] ?? null) ? implode(', ', $entry['tags']) : (string) ($entry['tags'] ?? '');
    $action = $isExisting ? '/admin/exhibits/' . rawurlencode($entry['slug']) : '/admin/exhibits/new';
    ob_start();
    ?>
    <section class="admin edit">
    <?php if ($error !== ''): ?><p class="form-error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <form method="post" action="<?= h($action) ?>" class="editor-form" enctype="multipart/form-data" data-editor-form><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="file" name="body_images[]" accept="image/jpeg,image/png,image/webp" multiple hidden data-body-image-upload><textarea name="body" id="body-markdown" hidden><?= form_value($entry, 'body') ?></textarea>
      <article class="exhibit editor-page">
        <div class="exhibit-toolbar"><a class="back-link" href="/admin">← Curator desk</a><span class="editor-state"><?= $isExisting ? 'Editing exhibit' : 'New exhibit' ?></span><?php if ($isExisting && ($entry['status'] ?? '') === 'published'): ?><a class="public-entry-link" href="/exhibits/<?= rawurlencode((string) $entry['slug']) ?>" target="_blank" rel="noopener noreferrer">View public version ↗</a><?php endif; ?><button type="submit" class="editor-top-save">Save exhibit</button></div>
        <header>
          <label class="eyebrow editor-label" for="page-category">Category</label>
          <select id="page-category" class="page-category" name="category" required data-category-select>
            <option value="" disabled <?= $category === '' ? 'selected' : '' ?>>Choose a category</option>
            <?php foreach ($categories as $option): ?><option value="<?= h($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= h($option) ?></option><?php endforeach; ?>
            <option value="__new__" <?= $newCategory ? 'selected' : '' ?>>Add new category…</option>
          </select>
          <label class="new-category-field" data-new-category-field <?= $newCategory ? '' : 'hidden' ?>>New category<input name="category_new" value="<?= $newCategory ? h($category) : '' ?>" data-new-category-input <?= $newCategory ? 'required' : '' ?>></label>
          <label class="page-title-label" for="page-title">Title</label><input id="page-title" class="page-title-input" name="title" value="<?= form_value($entry, 'title') ?>" required>
          <label class="page-summary-label" for="page-summary">Summary</label><textarea id="page-summary" class="page-summary-input" name="summary" rows="2" required><?= form_value($entry, 'summary') ?></textarea>
          <label class="editor-label tags-editor-label" for="page-tags">Tags <small>comma-separated; type to see existing tags</small></label>
          <div class="tag-autocomplete" data-tag-autocomplete>
            <input id="page-tags" class="page-tags-input" name="tags" value="<?= h($tags) ?>" autocomplete="off" aria-autocomplete="list" aria-controls="tag-suggestions" aria-expanded="false">
            <ul id="tag-suggestions" class="tag-suggestions" role="listbox" hidden></ul>
          </div>
          <datalist id="existing-tags"><?php foreach ($existingTags as $tag): ?><option value="<?= h($tag) ?>"></option><?php endforeach; ?></datalist>
        </header>
        <figure class="editor-image" data-editor-figure <?= ($entry['image'] ?? '') === '' ? 'hidden' : '' ?>><img data-editor-image<?= ($entry['image'] ?? '') !== '' ? ' src="' . h((string) $entry['image']) . '"' : '' ?> alt="Screenshot used in the talk"><figcaption>Screenshot used in the original talk.</figcaption></figure>
        <section class="prose editor-body">
          <div class="editor-body-heading"><h2>Exhibit content</h2><small>Paste a JPEG, PNG, or WebP screenshot up to 2 MB into the body. Select it to set its width in the toolbar.</small></div>
          <div class="format-toolbar" role="toolbar" aria-label="Text formatting">
            <button type="button" class="toolbar-button" data-format="p">Paragraph</button><button type="button" class="toolbar-button" data-format="h2">Heading</button><button type="button" class="toolbar-button" data-format="h3">Subheading</button><span aria-hidden="true"></span><button type="button" class="toolbar-button" data-format="bold" aria-label="Bold"><strong>B</strong></button><button type="button" class="toolbar-button" data-format="italic" aria-label="Italic"><em>I</em></button><button type="button" class="toolbar-button" data-format="ul">Bulleted list</button><button type="button" class="toolbar-button" data-format="pre">Code block</button><button type="button" class="toolbar-button" data-format="link">Link</button><label class="toolbar-image-size-label">Image width<select class="toolbar-image-size" data-body-image-size disabled><option value="25">25%</option><option value="50" selected>50%</option><option value="75">75%</option><option value="100">100%</option></select></label>
          </div>
          <div id="body-editor" class="visual-editor" contenteditable="true" role="textbox" aria-label="Exhibit content" aria-multiline="true" data-visual-editor><?= render_markdown((string) ($entry['body'] ?? '')) ?></div>
        </section>
        <p class="source-link editor-source"><label for="page-source">Primary source URL<input id="page-source" name="source" type="url" value="<?= form_value($entry, 'source') ?>"></label></p>
        <section class="editor-settings" aria-label="Exhibit settings"><h2>Exhibit settings</h2>
          <div class="field-row"><label>Path<input name="slug" value="<?= form_value($entry, 'slug') ?>" pattern="[a-z0-9]+(-[a-z0-9]+)*" required <?= $isExisting ? 'readonly' : '' ?>><small>lowercase words separated with hyphens</small></label><label>Status<select name="status"><option value="draft" <?= ($entry['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= ($entry['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option></select></label></div>
          <label>Screenshot<input name="image" value="<?= form_value($entry, 'image') ?>" placeholder="/static/images/talk/slide-7.webp"><small>Optional when you upload or paste a screenshot below.</small></label>
          <label>Upload screenshot<input type="file" name="image_upload" accept="image/jpeg,image/png,image/webp"><small>Choose a JPEG, PNG, or WebP up to 2 MB. The server saves the original and creates a 480-pixel WebP card thumbnail.</small></label>
          <div class="screenshot-paste-target" tabindex="0" role="region" aria-label="Paste screenshot here">Click here, then paste a screenshot with Cmd+V or Ctrl+V.</div>
        </section>
        <div class="editor-actions"><button type="submit">Save exhibit</button><a class="button secondary-button" href="<?= $isExisting && ($entry['status'] ?? '') === 'published' ? '/exhibits/' . rawurlencode($entry['slug']) : '/admin' ?>">Cancel</a></div>
      </article>
    </form>
    <?php if ($isExisting): ?><form method="post" action="<?= h($action) ?>/delete" class="delete-form" data-delete-form><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button class="danger-button" type="submit">Delete entry</button></form><?php endif; ?>
    </section>
    <?php
    layout(($isExisting ? 'Edit ' : 'New ') . $entry['title'] . ' · DNS Museum', (string) ob_get_clean());
}

try {
    if (str_starts_with($path, UPLOAD_URL)) { serve_uploaded_image($store, $path, $method); }
    if (($method === 'GET' || $method === 'HEAD') && ($path === '/about' || $path === '/categories')) {
        header('Location: ' . $path . '/', true, 301);
        exit;
    }
    if ($path === '/search-index.json' && ($method === 'GET' || $method === 'HEAD')) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        if ($method === 'HEAD') { exit; }
        try {
            $index = array_map(static fn (array $entry): array => [
                'slug' => $entry['slug'],
                'title' => $entry['title'],
                'summary' => $entry['summary'],
                'text' => implode(' ', [$entry['category'], implode(' ', $entry['tags']), $entry['body']]),
            ], $store->all());
            echo json_encode($index, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (Throwable $error) {
            error_log('Quick search unavailable: ' . $error->getMessage());
            http_response_code(500);
            echo '{"error":"Search is temporarily unavailable."}';
        }
        exit;
    }
    if ($path === '/sitemap.xml' && ($method === 'GET' || $method === 'HEAD')) {
        header('Content-Type: application/xml; charset=utf-8');
        if ($method === 'HEAD') { exit; }
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach (['/', '/categories/', '/about/'] as $page) {
            echo '<url><loc>https://dns.museum' . h($page) . '</loc></url>';
        }
        foreach ($store->all() as $entry) {
            echo '<url><loc>https://dns.museum/exhibits/' . rawurlencode($entry['slug']) . '</loc></url>';
        }
        echo '</urlset>';
        exit;
    }
    if ($path === '/' && ($method === 'GET' || $method === 'HEAD')) {
        $entries = $store->all();
        [$sectionLabels, $sections] = collection_sections($entries);
        ob_start(); ?>
        <section class="collection" id="collection"><div class="section-heading"><p class="eyebrow">DNS Museum · <?= count($entries) ?> entries</p><h1>The DNS Museum</h1><p>A reference for the odd, inventive, and always impractical things people have done with DNS.</p><div class="collection-search"><label for="collection-search">Search the collection</label><input id="collection-search" type="search" placeholder="Try a title, topic, or category" autocomplete="off" aria-describedby="search-status" data-collection-search><kbd class="collection-search-hint" aria-label="Press Command K or Control K to search all exhibits">⌘ K</kbd><p id="search-status" class="search-status" role="status" aria-live="polite"><?= count($entries) ?> exhibits</p></div></div><nav class="category-index" aria-label="Collection sections"><?php foreach ($sections as $category => $sectionEntries): if ($sectionEntries === []) { continue; } ?><a href="#<?= h(section_anchor($category)) ?>"><span><?= h($sectionLabels[$category]) ?></span><small><?= count($sectionEntries) ?></small></a><?php endforeach; ?></nav><?php foreach ($sections as $category => $sectionEntries): if ($sectionEntries === []) { continue; } ?><section class="collection-section" id="<?= h(section_anchor($category)) ?>"><div class="collection-section-heading"><h2><a class="category-heading-link" href="/categories/#<?= rawurlencode(section_anchor($category)) ?>"><?= h($sectionLabels[$category]) ?></a></h2><span><?= count($sectionEntries) ?> entries</span></div><div class="exhibit-grid"><?php foreach ($sectionEntries as $entry): ?><article class="exhibit-card" data-exhibit-card data-search-text="<?= h(implode(' ', [$entry['title'], $entry['summary'], $entry['category'], implode(' ', $entry['tags'])])) ?>"><div class="card-heading"><div class="card-title-line"><h3><a href="/exhibits/<?= rawurlencode($entry['slug']) ?>"><?= h($entry['title']) ?></a></h3><?= exhibit_edit_link($entry) ?></div><p class="eyebrow"><?= h($sectionLabels[$entry['category']] ?? $entry['category']) ?> · <?= h(implode(' · ', $entry['tags'])) ?></p></div><?php if ($entry['image'] !== ''): ?><div class="screenshot-frame"><img src="<?= h(thumbnail_image($entry['image'])) ?>" alt="Screenshot used in the talk for <?= h($entry['title']) ?>" loading="lazy"></div><?php endif; ?><div class="card-body"><p><?= h($entry['summary']) ?></p><a class="card-link" href="/exhibits/<?= rawurlencode($entry['slug']) ?>">Read more <span aria-hidden="true">→</span></a></div></article><?php endforeach; ?></div></section><?php endforeach; ?><p class="no-search-results" data-no-search-results hidden>No exhibits match your search.</p></section>
        <?php layout('The DNS Museum', (string) ob_get_clean()); exit;
    }
    if ($path === '/categories/' && ($method === 'GET' || $method === 'HEAD')) {
        $entries = $store->all();
        [$sectionLabels, $sections] = collection_sections($entries);
        ob_start(); ?>
        <section class="categories-page"><div class="section-heading"><p class="eyebrow">DNS Museum · <?= count($entries) ?> entries</p><h1>Categories</h1><p>Every entry, grouped by the way DNS is being used.</p></div><nav class="category-index" aria-label="Category sections"><?php foreach ($sections as $category => $sectionEntries): if ($sectionEntries === []) { continue; } ?><a href="#<?= h(section_anchor($category)) ?>"><span><?= h($sectionLabels[$category]) ?></span><small><?= count($sectionEntries) ?></small></a><?php endforeach; ?></nav><?php foreach ($sections as $category => $sectionEntries): if ($sectionEntries === []) { continue; } ?><section class="category-list-section" id="<?= h(section_anchor($category)) ?>"><div class="collection-section-heading"><h2><?= h($sectionLabels[$category]) ?></h2><span><?= count($sectionEntries) ?> entries</span></div><ul class="entry-list"><?php foreach ($sectionEntries as $entry): ?><li><div class="entry-title-line"><a href="/exhibits/<?= rawurlencode($entry['slug']) ?>"><?= h($entry['title']) ?></a><?= exhibit_edit_link($entry) ?></div><span><?= h($entry['summary']) ?></span></li><?php endforeach; ?></ul></section><?php endforeach; ?></section>
        <?php layout('Categories · DNS Museum', (string) ob_get_clean(), 'Browse the DNS Museum exhibits by the way they use DNS.'); exit;
    }
    if ($path === '/about/' && ($method === 'GET' || $method === 'HEAD')) {
        ob_start(); ?>
        <article class="exhibit"><a class="back-link" href="/#collection">← Back to collection</a><header><p class="eyebrow">About this project</p><h1>A museum of DNS</h1><p class="lede">A reference for the odd, inventive, and impractical things people have done with DNS.</p></header><section class="prose"><p>Hi. I'm Peter Lowe, and I made this site.</p><p>This site was created from a talk I've done a few times, "<a href="https://www.youtube.com/watch?v=Alz6e-95w4M">Bizarre and Unusual Uses of DNS</a>". At the start of the presentation, I joke that many of the things I describe are no longer online, so it's more like a "Museum of DNS" than a list of live services. I finally got around to putting up the site.</p><p>The collection records unusual and inventive ways people have used the Domain Name System. Each entry describes the idea, explains how DNS fits into it, and points to an original source where one is available. Some examples are historical exhibits; their original services may have changed or disappeared.</p><p>I'm also the founder of <a href="https://domainintelligence.uk/" rel="noopener noreferrer">Domain Intelligence</a>, a feed of malicious domains extracted from actively exploited malware with zero false positives.</p><p>For questions about the DNS Museum, email <a href="mailto:pgl@yoyo.org">pgl@yoyo.org</a>.</p><p>Unless a work states otherwise, original content by Peter Lowe is licensed under <a href="https://creativecommons.org/licenses/by/4.0/" rel="noopener noreferrer">CC BY 4.0</a>. The license does not cover third-party material.</p><p>AI helped create this site, but I'm responsible for the content, so please let me know about any mistakes.</p></section></article>
        <?php layout('About · DNS Museum', (string) ob_get_clean(), 'Learn how Peter Lowe’s talk about unusual uses of DNS became the DNS Museum.'); exit;
    }
    if (preg_match('#\A/exhibits/([a-z0-9-]+)\z#', $path, $matches) && ($method === 'GET' || $method === 'HEAD')) {
        try { $entry = $store->get($matches[1]); } catch (ExhibitNotFound|InvalidArgumentException $error) { header('Cache-Control: private, no-store'); http_response_code(404); exit('Not found'); }
        if ($entry['status'] !== 'published') { header('Cache-Control: private, no-store'); http_response_code(404); exit('Not found'); }
        $updatedDate = format_updated_date((string) $entry['updated']);
        ob_start(); ?>
        <article class="exhibit"><div class="exhibit-toolbar"><a class="back-link" href="/#collection">← Back to collection</a><?= exhibit_edit_link($entry) ?></div><header><p class="eyebrow"><a href="/categories/#<?= h(section_anchor($entry['category'])) ?>"><?= h($entry['category'] === 'DNS over something else' ? 'DNS over...' : $entry['category']) ?></a></p><h1><?= h($entry['title']) ?></h1><p class="lede"><?= h($entry['summary']) ?></p><p class="tags"><?php foreach ($entry['tags'] as $tag): ?><span><?= h($tag) ?></span><?php endforeach; ?></p><?php if ($updatedDate !== ''): ?><p class="updated-at">Date updated: <a href="https://github.com/pgl/dns-museum/commits/main/content/exhibits/<?= rawurlencode($entry['slug']) ?>.md" rel="noopener noreferrer"><time datetime="<?= h($entry['updated']) ?>"><?= h($updatedDate) ?></time></a></p><?php endif; ?></header><?php if ($entry['image'] !== ''): ?><figure><a class="full-size-image-link" href="<?= h($entry['image']) ?>" aria-label="View full-size image: Screenshot used in the talk for <?= h($entry['title']) ?>"><img src="<?= h($entry['image']) ?>" alt="Screenshot used in the talk for <?= h($entry['title']) ?>" width="1280" height="720"></a><figcaption>Screenshot used in the original talk. Select the image to view it full size.</figcaption></figure><?php endif; ?><section class="prose"><?= render_markdown($entry['body']) ?></section><?php if ($entry['source'] !== '' && safe_url($entry['source'])): ?><p class="source-link">Primary source: <a href="<?= h($entry['source']) ?>"><?= h($entry['source']) ?></a></p><?php endif; ?></article>
        <?php layout($entry['title'] . ' · DNS Museum', (string) ob_get_clean(), $entry['summary']); exit;
    }
    if ($path === '/admin/login' && $method === 'GET') {
        $loginCsrf = bin2hex(random_bytes(32));
        setcookie('museum_login_csrf', $loginCsrf, ['expires' => 0, 'path' => '/', 'secure' => $secureCookies, 'httponly' => true, 'samesite' => 'Strict']);
        layout('Curator sign in · DNS Museum', '<section class="auth"><p class="eyebrow">Staff only</p><h1>Curator sign in</h1><p>Use the password configured for this museum instance.</p><form method="post" action="/admin/login"><input type="hidden" name="csrf" value="' . h($loginCsrf) . '"><label for="password">Password</label><input id="password" name="password" type="password" required autofocus><button type="submit">Sign in</button></form></section>'); exit;
    }
    if ($path === '/admin/login' && $method === 'POST') {
        $submittedCsrf = (string) ($_POST['csrf'] ?? '');
        $cookieCsrf = (string) ($_COOKIE['museum_login_csrf'] ?? '');
        if ($submittedCsrf === '' || $cookieCsrf === '' || !hash_equals($cookieCsrf, $submittedCsrf)) { http_response_code(403); exit('Request expired. Reload and try again.'); }
        $remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $clientKey = hash('sha256', $remoteAddress);
        $limit = login_attempts($clientKey, true);
        if (!$limit['allowed']) { http_response_code(429); header('Retry-After: ' . $limit['retry_after']); exit('Too many sign-in attempts. Try again later.'); }
        if ($passwordConfigured && hash_equals($password, (string) ($_POST['password'] ?? ''))) { login_attempts($clientKey, false, true); setcookie('museum_login_csrf', '', ['expires' => time() - 3600, 'path' => '/', 'secure' => $secureCookies, 'httponly' => true, 'samesite' => 'Strict']); if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); } session_regenerate_id(true); $_SESSION['museum_admin'] = true; $_SESSION['museum_password_fingerprint'] = $passwordFingerprint; redirect('/admin'); }
        http_response_code(401); layout('Curator sign in · DNS Museum', '<section class="auth"><p class="form-error" role="alert">Sign in was not accepted.</p><a href="/admin/login">Try again</a></section>'); exit;
    }
    if ($path === '/admin/logout' && $method === 'POST') { require_admin(); if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } session_unset(); session_destroy(); redirect('/'); }
    if ($path === '/admin' && $method === 'GET') {
        require_admin(); $entries = $store->all(true); ob_start(); ?>
        <section class="admin"><div class="admin-heading"><div><p class="eyebrow">Authenticated area</p><h1>Curator desk</h1></div><div><a class="button" href="/admin/exhibits/new">New entry</a><form class="inline-form" method="post" action="/admin/logout"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button class="quiet-button" type="submit">Sign out</button></form></div></div><table><thead><tr><th>Entry</th><th>Category</th><th>Status</th><th>Updated</th></tr></thead><tbody><?php foreach ($entries as $entry): ?><tr><td><a href="/admin/exhibits/<?= rawurlencode($entry['slug']) ?>"><?= h($entry['title']) ?></a><small>/exhibits/<?= h($entry['slug']) ?></small></td><td><?= h($entry['category']) ?></td><td><span class="status <?= h($entry['status']) ?>"><?= h($entry['status']) ?></span></td><td><?= h(substr($entry['updated'], 0, 10)) ?></td></tr><?php endforeach; ?></tbody></table></section>
        <?php layout('Curator desk · DNS Museum', (string) ob_get_clean()); exit;
    }
    if ($path === '/admin/exhibits/new' && $method === 'GET') { require_admin(); editor(['slug' => '', 'title' => '', 'summary' => '', 'category' => '', 'tags' => [], 'image' => '', 'source' => '', 'status' => 'draft', 'body' => '']); exit; }
    if ($path === '/admin/exhibits/new' && $method === 'POST') { require_admin(); if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $entry = entry_from_request(); try { save_exhibit_with_uploads($store, $entry); redirect('/admin/exhibits/' . rawurlencode($entry['slug'])); } catch (Throwable $error) { http_response_code(422); editor($entry, $error->getMessage()); exit; } }
    if ($path === '/admin/exhibits/new/preview' && $method === 'POST') { require_admin(); if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $entry = entry_from_request(); editor($entry, '', render_markdown($entry['body'])); exit; }
    if (preg_match('#\A/admin/exhibits/([a-z0-9-]+)\z#', $path, $matches)) {
        require_admin(); $slug = $matches[1];
        if ($method === 'GET') { editor($store->get($slug)); exit; }
        if ($method === 'POST') { if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $entry = entry_from_request(); if ($entry['slug'] !== $slug) { http_response_code(422); editor($entry, 'The path cannot change after creation.'); exit; } try { save_exhibit_with_uploads($store, $entry); redirect('/admin/exhibits/' . rawurlencode($slug)); } catch (Throwable $error) { http_response_code(422); editor($entry, $error->getMessage()); exit; } }
    }
    if (preg_match('#\A/admin/exhibits/([a-z0-9-]+)/preview\z#', $path, $matches) && $method === 'POST') { require_admin(); if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $entry = entry_from_request(); editor($entry, '', render_markdown($entry['body'])); exit; }
    if (preg_match('#\A/admin/exhibits/([a-z0-9-]+)/delete\z#', $path, $matches) && $method === 'POST') { require_admin(); if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $store->delete($matches[1]); redirect('/admin'); }
    http_response_code(404); layout('Not found · DNS Museum', '<section class="auth"><h1>Not found</h1><p>That entry is not in the collection.</p></section>');
} catch (Throwable $error) {
    error_log($error->getMessage()); header('Cache-Control: private, no-store'); http_response_code(500); layout('Error · DNS Museum', '<section class="auth"><h1>Catalogue unavailable</h1><p>Please try again later.</p></section>');
}
