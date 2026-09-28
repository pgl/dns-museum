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
session_start();

$store = new ExhibitStore(dirname(__DIR__) . '/content/exhibits');
$password = config_value('MUSEUM_ADMIN_PASSWORD');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' https:; style-src 'self'; script-src 'self'; base-uri 'self'; form-action 'self'");

function layout(string $title, string $content): void {
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="DNS Museum: a field guide to unusual uses of the Domain Name System."><title>' . h($title) . '</title><link rel="stylesheet" href="/static/css/site.css"></head><body>';
    echo '<a class="skip-link" href="#content">Skip to content</a><header class="site-header"><a class="wordmark" href="/"><span>The</span><strong>DNS</strong><span>Museum</span></a><nav class="site-nav" aria-label="Main navigation"><a href="/#collection">Collection</a><a href="/categories/">Categories</a><a href="/about/">About</a></nav></header><main id="content">' . $content . '</main>';
    echo '<footer><p>A field guide to unusual uses of DNS. Original content by Peter Lowe is licensed under <a href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a>; third-party material is excluded. <span class="footer-separator" aria-hidden="true">•</span> <a href="/about/">About</a> <span class="footer-separator" aria-hidden="true">•</span> <a href="https://github.com/pgl/dns-museum">GitHub</a></p></footer><script src="/static/js/admin.js" defer></script></body></html>';
}

function redirect(string $to): never { header('Location: ' . $to, true, 303); exit; }
function require_admin(): void { if (empty($_SESSION['museum_admin'])) { redirect('/admin/login'); } }
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
function store_uploaded_image(string $slug): string {
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
    $directory = dirname(__DIR__) . '/public/static/images/uploads';
    $thumbDirectory = $directory . '/thumbnails';
    foreach ([$directory, $thumbDirectory] as $path) {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) { throw new RuntimeException('The image directory could not be created.'); }
    }
    $extension = match ($info[2]) { IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp' };
    $original = $directory . '/' . $slug . '.' . $extension;
    $thumbnail = $thumbDirectory . '/' . $slug . '.webp';
    if (!move_uploaded_file((string) $upload['tmp_name'], $original)) { throw new RuntimeException('The uploaded image could not be saved.'); }
    $convert = config_value('MUSEUM_IMAGE_MAGICK', '/usr/bin/convert');
    $temporaryThumbnail = $thumbDirectory . '/.' . $slug . '-' . bin2hex(random_bytes(6)) . '.webp';
    if (!is_executable($convert) || !function_exists('proc_open')) { @unlink($original); throw new RuntimeException('ImageMagick is not available to create the thumbnail.'); }
    $process = proc_open([$convert, $original, '-auto-orient', '-thumbnail', '640x640>', '-strip', '-quality', '82', '-define', 'webp:method=6', 'webp:' . $temporaryThumbnail], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    $status = is_resource($process) ? proc_close($process) : -1;
    $thumbnailInfo = $status === 0 ? @getimagesize($temporaryThumbnail) : false;
    if ($thumbnailInfo === false || $thumbnailInfo[2] !== IMAGETYPE_WEBP || max($thumbnailInfo[0], $thumbnailInfo[1]) > 640 || !rename($temporaryThumbnail, $thumbnail)) {
        @unlink($temporaryThumbnail);
        @unlink($original);
        throw new RuntimeException('The thumbnail could not be created. Check that ImageMagick supports WebP.');
    }
    return '/static/images/uploads/' . $slug . '.' . $extension;
}

function editor(array $entry, string $error = '', string $preview = ''): void {
    $isExisting = $entry['slug'] !== '';
    $tags = is_array($entry['tags'] ?? null) ? implode(', ', $entry['tags']) : (string) ($entry['tags'] ?? '');
    $action = $isExisting ? '/admin/exhibits/' . rawurlencode($entry['slug']) : '/admin/exhibits/new';
    ob_start();
    ?>
    <section class="admin edit">
    <?php if ($error !== ''): ?><p class="form-error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <form method="post" action="<?= h($action) ?>" class="editor-form" enctype="multipart/form-data" data-editor-form><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><textarea name="body" id="body-markdown" hidden><?= form_value($entry, 'body') ?></textarea>
      <article class="exhibit editor-page">
        <div class="exhibit-toolbar"><a class="back-link" href="/admin">← Curator desk</a><span class="editor-state"><?= $isExisting ? 'Editing exhibit' : 'New exhibit' ?></span><button type="submit" class="editor-top-save">Save exhibit</button></div>
        <header>
          <label class="eyebrow editor-label">Category<input class="page-category" name="category" value="<?= form_value($entry, 'category') ?>" required aria-label="Category"></label>
          <label class="page-title-label" for="page-title">Title</label><input id="page-title" class="page-title-input" name="title" value="<?= form_value($entry, 'title') ?>" required>
          <label class="page-summary-label" for="page-summary">Summary</label><textarea id="page-summary" class="page-summary-input" name="summary" rows="2" required><?= form_value($entry, 'summary') ?></textarea>
          <label class="editor-label tags-editor-label" for="page-tags">Tags <small>comma-separated</small></label><input id="page-tags" class="page-tags-input" name="tags" value="<?= h($tags) ?>">
        </header>
        <figure class="editor-image" data-editor-figure <?= ($entry['image'] ?? '') === '' ? 'hidden' : '' ?>><img data-editor-image<?= ($entry['image'] ?? '') !== '' ? ' src="' . h((string) $entry['image']) . '"' : '' ?> alt="Screenshot used in the talk"><figcaption>Screenshot used in the original talk.</figcaption></figure>
        <section class="prose editor-body">
          <div class="editor-body-heading"><h2>Exhibit content</h2><small>Format the text as it will appear on the exhibit page.</small></div>
          <div class="format-toolbar" role="toolbar" aria-label="Text formatting">
            <button type="button" class="toolbar-button" data-format="p">Paragraph</button><button type="button" class="toolbar-button" data-format="h2">Heading</button><button type="button" class="toolbar-button" data-format="h3">Subheading</button><span aria-hidden="true"></span><button type="button" class="toolbar-button" data-format="bold" aria-label="Bold"><strong>B</strong></button><button type="button" class="toolbar-button" data-format="italic" aria-label="Italic"><em>I</em></button><button type="button" class="toolbar-button" data-format="ul">Bulleted list</button><button type="button" class="toolbar-button" data-format="pre">Code block</button><button type="button" class="toolbar-button" data-format="link">Link</button>
          </div>
          <div id="body-editor" class="visual-editor" contenteditable="true" role="textbox" aria-label="Exhibit content" aria-multiline="true" data-visual-editor><?= render_markdown((string) ($entry['body'] ?? '')) ?></div>
        </section>
        <p class="source-link editor-source"><label for="page-source">Primary source URL<input id="page-source" name="source" type="url" value="<?= form_value($entry, 'source') ?>"></label></p>
        <section class="editor-settings" aria-label="Exhibit settings"><h2>Exhibit settings</h2>
          <div class="field-row"><label>Path<input name="slug" value="<?= form_value($entry, 'slug') ?>" pattern="[a-z0-9]+(-[a-z0-9]+)*" required <?= $isExisting ? 'readonly' : '' ?>><small>lowercase words separated with hyphens</small></label><label>Status<select name="status"><option value="draft" <?= ($entry['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= ($entry['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option></select></label></div>
          <label>Slide image path<input name="image" value="<?= form_value($entry, 'image') ?>" placeholder="/static/images/talk/slide-7.webp"><small>Optional when you upload an image below.</small></label>
          <label>Upload slide image<input type="file" name="image_upload" accept="image/jpeg,image/png,image/webp"><small>JPEG, PNG, or WebP up to 2 MB. The server saves the original and creates a 640-pixel WebP card thumbnail.</small></label>
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
    if ($path === '/' && $method === 'GET') {
        $entries = $store->all();
        [$sectionLabels, $sections] = collection_sections($entries);
        ob_start(); ?>
        <section class="collection" id="collection"><div class="section-heading"><p class="eyebrow">DNS Museum · <?= count($entries) ?> entries</p><h1>The collection</h1><p>A reference for the odd, inventive, and always impractical things people have done with DNS.</p></div><nav class="category-index" aria-label="Collection sections"><?php foreach ($sections as $category => $sectionEntries): if ($sectionEntries === []) { continue; } ?><a href="#<?= h(section_anchor($category)) ?>"><span><?= h($sectionLabels[$category]) ?></span><small><?= count($sectionEntries) ?></small></a><?php endforeach; ?></nav><?php foreach ($sections as $category => $sectionEntries): if ($sectionEntries === []) { continue; } ?><section class="collection-section" id="<?= h(section_anchor($category)) ?>"><div class="collection-section-heading"><h2><a class="category-heading-link" href="/categories/#<?= rawurlencode(section_anchor($category)) ?>"><?= h($sectionLabels[$category]) ?></a></h2><span><?= count($sectionEntries) ?> entries</span></div><div class="exhibit-grid"><?php foreach ($sectionEntries as $entry): ?><article class="exhibit-card"><div class="card-heading"><div class="card-title-line"><h3><a href="/exhibits/<?= rawurlencode($entry['slug']) ?>"><?= h($entry['title']) ?></a></h3><?= exhibit_edit_link($entry) ?></div><p class="eyebrow"><?= h($sectionLabels[$entry['category']] ?? $entry['category']) ?></p></div><?php if ($entry['image'] !== ''): ?><div class="screenshot-frame"><img src="<?= h(thumbnail_image($entry['image'])) ?>" alt="Screenshot used in the talk for <?= h($entry['title']) ?>" loading="lazy"></div><?php endif; ?><div class="card-body"><p><?= h($entry['summary']) ?></p><a class="card-link" href="/exhibits/<?= rawurlencode($entry['slug']) ?>">Read more <span aria-hidden="true">→</span></a></div></article><?php endforeach; ?></div></section><?php endforeach; ?></section>
        <?php layout('DNS Museum', (string) ob_get_clean()); exit;
    }
    if (($path === '/categories' || $path === '/categories/') && $method === 'GET') {
        $entries = $store->all();
        [$sectionLabels, $sections] = collection_sections($entries);
        ob_start(); ?>
        <section class="categories-page"><div class="section-heading"><p class="eyebrow">DNS Museum · <?= count($entries) ?> entries</p><h1>Categories</h1><p>Every entry, grouped by the way DNS is being used.</p></div><nav class="category-index" aria-label="Category sections"><?php foreach ($sections as $category => $sectionEntries): if ($sectionEntries === []) { continue; } ?><a href="#<?= h(section_anchor($category)) ?>"><span><?= h($sectionLabels[$category]) ?></span><small><?= count($sectionEntries) ?></small></a><?php endforeach; ?></nav><?php foreach ($sections as $category => $sectionEntries): if ($sectionEntries === []) { continue; } ?><section class="category-list-section" id="<?= h(section_anchor($category)) ?>"><div class="collection-section-heading"><h2><?= h($sectionLabels[$category]) ?></h2><span><?= count($sectionEntries) ?> entries</span></div><ul class="entry-list"><?php foreach ($sectionEntries as $entry): ?><li><div class="entry-title-line"><a href="/exhibits/<?= rawurlencode($entry['slug']) ?>"><?= h($entry['title']) ?></a><?= exhibit_edit_link($entry) ?></div><span><?= h($entry['summary']) ?></span></li><?php endforeach; ?></ul></section><?php endforeach; ?></section>
        <?php layout('Categories · DNS Museum', (string) ob_get_clean()); exit;
    }
    if (($path === '/about' || $path === '/about/') && $method === 'GET') {
        ob_start(); ?>
        <article class="exhibit"><a class="back-link" href="/#collection">← Back to collection</a><header><p class="eyebrow">About this project</p><h1>A museum of DNS</h1><p class="lede">A reference for the odd, inventive, and impractical things people have done with DNS.</p></header><section class="prose"><p>This site was created from a talk I've done a few times, "<a href="https://www.youtube.com/watch?v=Alz6e-95w4M">Bizarre and Unusual Uses of DNS</a>". At the start of the presentation, I joke that many of the things I describe are no longer online, so it's more like a "Museum of DNS" than a list of live services. I finally got around to putting up the site.</p><p>The collection records unusual and inventive ways people have used the Domain Name System. Each entry describes the idea, explains how DNS fits into it, and points to an original source where one is available. Some examples are historical exhibits; their original services may have changed or disappeared.</p><p>I'm also the founder of <a href="https://domainintelligence.uk/" rel="noopener noreferrer">Domain Intelligence</a>, a feed of malicious domains extracted from actively exploited malware with zero false positives.</p><p>For questions about the DNS Museum, email <a href="mailto:pgl@yoyo.org">pgl@yoyo.org</a>.</p><p>Unless a work states otherwise, original content by Peter Lowe is licensed under <a href="https://creativecommons.org/licenses/by/4.0/" rel="noopener noreferrer">CC BY 4.0</a>. The license does not cover third-party material.</p><p>AI helped create this site, but I'm responsible for the content, so please let me know about any mistakes.</p></section></article>
        <?php layout('About · DNS Museum', (string) ob_get_clean()); exit;
    }
    if (preg_match('#\A/exhibits/([a-z0-9-]+)\z#', $path, $matches) && $method === 'GET') {
        $entry = $store->get($matches[1]); if ($entry['status'] !== 'published') { http_response_code(404); exit('Not found'); }
        $updatedDate = format_updated_date((string) $entry['updated']);
        ob_start(); ?>
        <article class="exhibit"><div class="exhibit-toolbar"><a class="back-link" href="/#collection">← Back to collection</a><?= exhibit_edit_link($entry) ?></div><header><p class="eyebrow"><a href="/categories/#<?= h(section_anchor($entry['category'])) ?>"><?= h($entry['category'] === 'DNS over something else' ? 'DNS over...' : $entry['category']) ?></a></p><h1><?= h($entry['title']) ?></h1><p class="lede"><?= h($entry['summary']) ?></p><p class="tags"><?php foreach ($entry['tags'] as $tag): ?><span><?= h($tag) ?></span><?php endforeach; ?></p><?php if ($updatedDate !== ''): ?><p class="updated-at">Date updated: <a href="https://github.com/pgl/dns-museum/commits/main/content/exhibits/<?= rawurlencode($entry['slug']) ?>.md" rel="noopener noreferrer"><time datetime="<?= h($entry['updated']) ?>"><?= h($updatedDate) ?></time></a></p><?php endif; ?></header><?php if ($entry['image'] !== ''): ?><figure><img src="<?= h($entry['image']) ?>" alt="Screenshot used in the talk for <?= h($entry['title']) ?>" width="1280" height="720"><figcaption>Screenshot used in the original talk.</figcaption></figure><?php endif; ?><section class="prose"><?= render_markdown($entry['body']) ?></section><?php if ($entry['source'] !== '' && safe_url($entry['source'])): ?><p class="source-link">Primary source: <a href="<?= h($entry['source']) ?>"><?= h($entry['source']) ?></a></p><?php endif; ?></article>
        <?php layout($entry['title'] . ' · DNS Museum', (string) ob_get_clean()); exit;
    }
    if ($path === '/admin/login' && $method === 'GET') { layout('Curator sign in · DNS Museum', '<section class="auth"><p class="eyebrow">Staff only</p><h1>Curator sign in</h1><p>Use the password configured for this museum instance.</p><form method="post" action="/admin/login"><label for="password">Password</label><input id="password" name="password" type="password" required autofocus><button type="submit">Sign in</button></form></section>'); exit; }
    if ($path === '/admin/login' && $method === 'POST') {
        if ($password !== '' && hash_equals($password, (string) ($_POST['password'] ?? ''))) { session_regenerate_id(true); $_SESSION['museum_admin'] = true; redirect('/admin'); }
        http_response_code(401); layout('Curator sign in · DNS Museum', '<section class="auth"><p class="form-error" role="alert">Sign in was not accepted.</p><a href="/admin/login">Try again</a></section>'); exit;
    }
    if ($path === '/admin/logout' && $method === 'POST') { session_unset(); session_destroy(); redirect('/'); }
    if ($path === '/admin' && $method === 'GET') {
        require_admin(); $entries = $store->all(true); ob_start(); ?>
        <section class="admin"><div class="admin-heading"><div><p class="eyebrow">Authenticated area</p><h1>Curator desk</h1></div><div><a class="button" href="/admin/exhibits/new">New entry</a><form class="inline-form" method="post" action="/admin/logout"><button class="quiet-button" type="submit">Sign out</button></form></div></div><table><thead><tr><th>Entry</th><th>Category</th><th>Status</th><th>Updated</th></tr></thead><tbody><?php foreach ($entries as $entry): ?><tr><td><a href="/admin/exhibits/<?= rawurlencode($entry['slug']) ?>"><?= h($entry['title']) ?></a><small>/exhibits/<?= h($entry['slug']) ?></small></td><td><?= h($entry['category']) ?></td><td><span class="status <?= h($entry['status']) ?>"><?= h($entry['status']) ?></span></td><td><?= h(substr($entry['updated'], 0, 10)) ?></td></tr><?php endforeach; ?></tbody></table></section>
        <?php layout('Curator desk · DNS Museum', (string) ob_get_clean()); exit;
    }
    if ($path === '/admin/exhibits/new' && $method === 'GET') { require_admin(); editor(['slug' => '', 'title' => '', 'summary' => '', 'category' => '', 'tags' => [], 'image' => '', 'source' => '', 'status' => 'draft', 'body' => '']); exit; }
    if ($path === '/admin/exhibits/new' && $method === 'POST') { require_admin(); if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $entry = entry_from_request(); try { $uploaded = store_uploaded_image($entry['slug']); if ($uploaded !== '') { $entry['image'] = $uploaded; } $store->save($entry); redirect('/admin/exhibits/' . rawurlencode($entry['slug'])); } catch (Throwable $error) { http_response_code(422); editor($entry, $error->getMessage()); exit; } }
    if ($path === '/admin/exhibits/new/preview' && $method === 'POST') { require_admin(); if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $entry = entry_from_request(); editor($entry, '', render_markdown($entry['body'])); exit; }
    if (preg_match('#\A/admin/exhibits/([a-z0-9-]+)\z#', $path, $matches)) {
        require_admin(); $slug = $matches[1];
        if ($method === 'GET') { editor($store->get($slug)); exit; }
        if ($method === 'POST') { if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $entry = entry_from_request(); if ($entry['slug'] !== $slug) { http_response_code(422); editor($entry, 'The path cannot change after creation.'); exit; } try { $uploaded = store_uploaded_image($slug); if ($uploaded !== '') { $entry['image'] = $uploaded; } $store->save($entry); redirect('/admin/exhibits/' . rawurlencode($slug)); } catch (Throwable $error) { http_response_code(422); editor($entry, $error->getMessage()); exit; } }
    }
    if (preg_match('#\A/admin/exhibits/([a-z0-9-]+)/preview\z#', $path, $matches) && $method === 'POST') { require_admin(); if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $entry = entry_from_request(); editor($entry, '', render_markdown($entry['body'])); exit; }
    if (preg_match('#\A/admin/exhibits/([a-z0-9-]+)/delete\z#', $path, $matches) && $method === 'POST') { require_admin(); if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $store->delete($matches[1]); redirect('/admin'); }
    http_response_code(404); layout('Not found · DNS Museum', '<section class="auth"><h1>Not found</h1><p>That entry is not in the collection.</p></section>');
} catch (Throwable $error) {
    error_log($error->getMessage()); http_response_code(500); layout('Error · DNS Museum', '<section class="auth"><h1>Catalogue unavailable</h1><p>Please try again later.</p></section>');
}
