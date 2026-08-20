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
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="A field guide to unusual uses of the Domain Name System."><title>' . h($title) . '</title><link rel="stylesheet" href="/static/css/site.css"></head><body>';
    echo '<a class="skip-link" href="#content">Skip to content</a><header class="site-header"><a class="wordmark" href="/">Museum <span>of</span> DNS</a><nav aria-label="Main navigation"><a href="/#collection">Collection</a><a href="/categories/">Categories</a><a href="/admin">Curator desk</a></nav></header><main id="content">' . $content . '</main>';
    echo '<footer><p>A field guide to unusual uses of DNS.</p></footer><script src="/static/js/admin.js" defer></script></body></html>';
}

function redirect(string $to): never { header('Location: ' . $to, true, 303); exit; }
function require_admin(): void { if (empty($_SESSION['museum_admin'])) { redirect('/admin/login'); } }
function form_value(array $entry, string $key): string { return h((string) ($entry[$key] ?? '')); }
function collection_sections(array $entries): array {
    $labels = ['Traceroutes' => 'Traceroutes', 'DNS over something else' => 'DNS over…', 'Tools and toys' => 'Tools and toys', 'Tunnelling' => 'Tunnelling', 'Other things' => 'Other things'];
    $sections = array_fill_keys(array_keys($labels), []);
    foreach ($entries as $entry) {
        if (!array_key_exists($entry['category'], $sections)) { $labels[$entry['category']] = $entry['category']; $sections[$entry['category']] = []; }
        $sections[$entry['category']][] = $entry;
    }
    return [$labels, $sections];
}
function section_anchor(string $category): string { return strtolower(str_replace(' ', '-', str_replace(' something else', '', $category))); }

function editor(array $entry, string $error = '', string $preview = ''): void {
    $isExisting = $entry['slug'] !== '';
    $tags = is_array($entry['tags'] ?? null) ? implode(', ', $entry['tags']) : (string) ($entry['tags'] ?? '');
    $action = $isExisting ? '/admin/exhibits/' . rawurlencode($entry['slug']) : '/admin/exhibits/new';
    ob_start();
    ?>
    <section class="admin edit"><a class="back-link" href="/admin">← Curator desk</a><p class="eyebrow"><?= $isExisting ? 'Edit entry' : 'New entry' ?></p><h1><?= $isExisting ? h($entry['title']) : 'New entry' ?></h1>
    <?php if ($error !== ''): ?><p class="form-error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <form method="post" action="<?= h($action) ?>" class="editor-form"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <div class="field-row"><label>Title<input name="title" value="<?= form_value($entry, 'title') ?>" required></label><label>Path<input name="slug" value="<?= form_value($entry, 'slug') ?>" pattern="[a-z0-9]+(-[a-z0-9]+)*" required <?= $isExisting ? 'readonly' : '' ?>><small>lowercase words separated with hyphens</small></label></div>
    <label>Summary<textarea name="summary" rows="3" required><?= form_value($entry, 'summary') ?></textarea></label><div class="field-row"><label>Category<input name="category" value="<?= form_value($entry, 'category') ?>" required></label><label>Tags<input name="tags" value="<?= h($tags) ?>"><small>comma-separated</small></label></div>
    <label>Slide image path<input name="image" value="<?= form_value($entry, 'image') ?>" placeholder="/static/images/talk/slide-7.webp"></label><label>Primary source URL<input name="source" value="<?= form_value($entry, 'source') ?>" type="url"></label><label>Status<select name="status"><option value="draft" <?= ($entry['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= ($entry['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option></select></label>
    <label>Markdown<textarea name="body" rows="18" required><?= form_value($entry, 'body') ?></textarea></label><div class="editor-actions"><button type="submit">Save entry</button><button class="secondary-button" formaction="<?= h($action) ?>/preview" formmethod="post">Preview without saving</button></div></form>
    <?php if ($isExisting): ?><form method="post" action="<?= h($action) ?>/delete" class="delete-form" data-delete-form><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button class="danger-button" type="submit">Delete entry</button></form><?php endif; ?>
    <?php if ($preview !== ''): ?><section class="preview"><p class="eyebrow">Unsaved preview</p><h2><?= h($entry['title']) ?></h2><?php if ($entry['image'] !== ''): ?><img src="<?= h($entry['image']) ?>" alt="Preview slide image" width="1280" height="720"><?php endif; ?><div class="prose"><?= $preview ?></div></section><?php endif; ?></section>
    <?php
    layout(($isExisting ? 'Edit ' : 'New ') . $entry['title'], (string) ob_get_clean());
}

try {
    if ($path === '/' && $method === 'GET') {
        $entries = $store->all();
        [$sectionLabels, $sections] = collection_sections($entries);
        ob_start(); ?>
        <section class="collection" id="collection"><div class="section-heading"><p class="eyebrow">Museum of DNS · <?= count($entries) ?> entries</p><h1>The collection</h1></div><?php foreach ($sections as $category => $sectionEntries): if ($sectionEntries === []) { continue; } ?><section class="collection-section" id="<?= h(section_anchor($category)) ?>"><h2><a class="category-heading-link" href="/categories/#<?= rawurlencode(section_anchor($category)) ?>"><?= h($sectionLabels[$category]) ?></a></h2><div class="exhibit-grid"><?php foreach ($sectionEntries as $entry): ?><article class="exhibit-card"><div class="card-heading"><h3><a href="/exhibits/<?= rawurlencode($entry['slug']) ?>"><?= h($entry['title']) ?></a></h3><p class="eyebrow"><?= h($entry['category']) ?></p></div><?php if ($entry['image'] !== ''): ?><div class="screenshot-frame"><img src="<?= h($entry['image']) ?>" alt="Screenshot used in the talk for <?= h($entry['title']) ?>" loading="lazy" width="1280" height="720"></div><?php endif; ?><div class="card-body"><p><?= h($entry['summary']) ?></p><a class="card-link" href="/exhibits/<?= rawurlencode($entry['slug']) ?>">Read more <span aria-hidden="true">→</span></a></div></article><?php endforeach; ?></div></section><?php endforeach; ?></section>
        <?php layout('Museum of DNS', (string) ob_get_clean()); exit;
    }
    if (($path === '/categories' || $path === '/categories/') && $method === 'GET') {
        $entries = $store->all();
        [$sectionLabels, $sections] = collection_sections($entries);
        ob_start(); ?>
        <section class="categories-page"><div class="section-heading"><p class="eyebrow">Museum of DNS · <?= count($entries) ?> entries</p><h1>Categories</h1><p>Every entry, grouped by the way DNS is being used.</p></div><?php foreach ($sections as $category => $sectionEntries): if ($sectionEntries === []) { continue; } ?><section class="category-list-section" id="<?= h(section_anchor($category)) ?>"><h2><?= h($sectionLabels[$category]) ?></h2><ul class="entry-list"><?php foreach ($sectionEntries as $entry): ?><li><a href="/exhibits/<?= rawurlencode($entry['slug']) ?>"><?= h($entry['title']) ?></a><span><?= h($entry['summary']) ?></span></li><?php endforeach; ?></ul></section><?php endforeach; ?></section>
        <?php layout('Categories · Museum of DNS', (string) ob_get_clean()); exit;
    }
    if (preg_match('#\A/exhibits/([a-z0-9-]+)\z#', $path, $matches) && $method === 'GET') {
        $entry = $store->get($matches[1]); if ($entry['status'] !== 'published') { http_response_code(404); exit('Not found'); }
        ob_start(); ?>
        <article class="exhibit"><a class="back-link" href="/#collection">← Back to collection</a><header><p class="eyebrow"><?= h($entry['category']) ?></p><h1><?= h($entry['title']) ?></h1><p class="lede"><?= h($entry['summary']) ?></p><p class="tags"><?php foreach ($entry['tags'] as $tag): ?><span><?= h($tag) ?></span><?php endforeach; ?></p></header><?php if ($entry['image'] !== ''): ?><figure><img src="<?= h($entry['image']) ?>" alt="Screenshot used in the talk for <?= h($entry['title']) ?>" width="1280" height="720"><figcaption>Screenshot used in the original talk.</figcaption></figure><?php endif; ?><section class="prose"><?= render_markdown($entry['body']) ?></section><?php if ($entry['source'] !== '' && safe_url($entry['source'])): ?><p class="source-link">Primary source: <a href="<?= h($entry['source']) ?>"><?= h($entry['source']) ?></a></p><?php endif; ?></article>
        <?php layout($entry['title'] . ' · Museum of DNS', (string) ob_get_clean()); exit;
    }
    if ($path === '/admin/login' && $method === 'GET') { layout('Curator sign in', '<section class="auth"><p class="eyebrow">Staff only</p><h1>Curator sign in</h1><p>Use the password configured for this museum instance.</p><form method="post" action="/admin/login"><label for="password">Password</label><input id="password" name="password" type="password" required autofocus><button type="submit">Sign in</button></form></section>'); exit; }
    if ($path === '/admin/login' && $method === 'POST') {
        if ($password !== '' && hash_equals($password, (string) ($_POST['password'] ?? ''))) { session_regenerate_id(true); $_SESSION['museum_admin'] = true; redirect('/admin'); }
        http_response_code(401); layout('Curator sign in', '<section class="auth"><p class="form-error" role="alert">Sign in was not accepted.</p><a href="/admin/login">Try again</a></section>'); exit;
    }
    if ($path === '/admin/logout' && $method === 'POST') { session_unset(); session_destroy(); redirect('/'); }
    if ($path === '/admin' && $method === 'GET') {
        require_admin(); $entries = $store->all(true); ob_start(); ?>
        <section class="admin"><div class="admin-heading"><div><p class="eyebrow">Authenticated area</p><h1>Curator desk</h1></div><div><a class="button" href="/admin/exhibits/new">New entry</a><form class="inline-form" method="post" action="/admin/logout"><button class="quiet-button" type="submit">Sign out</button></form></div></div><table><thead><tr><th>Entry</th><th>Category</th><th>Status</th><th>Updated</th></tr></thead><tbody><?php foreach ($entries as $entry): ?><tr><td><a href="/admin/exhibits/<?= rawurlencode($entry['slug']) ?>"><?= h($entry['title']) ?></a><small>/exhibits/<?= h($entry['slug']) ?></small></td><td><?= h($entry['category']) ?></td><td><span class="status <?= h($entry['status']) ?>"><?= h($entry['status']) ?></span></td><td><?= h(substr($entry['updated'], 0, 10)) ?></td></tr><?php endforeach; ?></tbody></table></section>
        <?php layout('Curator desk', (string) ob_get_clean()); exit;
    }
    if ($path === '/admin/exhibits/new' && $method === 'GET') { require_admin(); editor(['slug' => '', 'title' => '', 'summary' => '', 'category' => '', 'tags' => [], 'image' => '', 'source' => '', 'status' => 'draft', 'body' => '']); exit; }
    if ($path === '/admin/exhibits/new' && $method === 'POST') { require_admin(); if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $entry = entry_from_request(); try { $store->save($entry); redirect('/admin/exhibits/' . rawurlencode($entry['slug'])); } catch (Throwable $error) { http_response_code(422); editor($entry, $error->getMessage()); exit; } }
    if ($path === '/admin/exhibits/new/preview' && $method === 'POST') { require_admin(); if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $entry = entry_from_request(); editor($entry, '', render_markdown($entry['body'])); exit; }
    if (preg_match('#\A/admin/exhibits/([a-z0-9-]+)\z#', $path, $matches)) {
        require_admin(); $slug = $matches[1];
        if ($method === 'GET') { editor($store->get($slug)); exit; }
        if ($method === 'POST') { if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $entry = entry_from_request(); if ($entry['slug'] !== $slug) { http_response_code(422); editor($entry, 'The path cannot change after creation.'); exit; } try { $store->save($entry); redirect('/admin/exhibits/' . rawurlencode($slug)); } catch (Throwable $error) { http_response_code(422); editor($entry, $error->getMessage()); exit; } }
    }
    if (preg_match('#\A/admin/exhibits/([a-z0-9-]+)/preview\z#', $path, $matches) && $method === 'POST') { require_admin(); if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $entry = entry_from_request(); editor($entry, '', render_markdown($entry['body'])); exit; }
    if (preg_match('#\A/admin/exhibits/([a-z0-9-]+)/delete\z#', $path, $matches) && $method === 'POST') { require_admin(); if (!csrf_valid()) { http_response_code(403); exit('Request expired. Reload and try again.'); } $store->delete($matches[1]); redirect('/admin'); }
    http_response_code(404); layout('Not found', '<section class="auth"><h1>Not found</h1><p>That entry is not in the collection.</p></section>');
} catch (Throwable $error) {
    error_log($error->getMessage()); http_response_code(500); layout('Museum error', '<section class="auth"><h1>Catalogue unavailable</h1><p>Please try again later.</p></section>');
}
