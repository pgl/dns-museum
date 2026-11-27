# Development and deployment

## Run locally with nginx

```sh
cp .env.example .env
brew install nginx
cp nginx/dns-museum.conf /opt/homebrew/etc/nginx/servers/dns-museum.conf
brew services start nginx
```

Open `http://127.0.0.1:8081`. The collection is at `/`; sign in at `/admin`. The development nginx listener binds to loopback only.

Set a long, unique `MUSEUM_ADMIN_PASSWORD` in `.env` before using the curator desk. The example leaves it empty, which disables password login until you configure it. The old sample value `replace-with-a-long-unique-password` is rejected. If an instance used that value, replace it with a new secret; sessions created with the sample value are invalidated. Set `MUSEUM_SECURE_COOKIES=true` when the site uses HTTPS.

## Content model

Each exhibit is one Markdown file in `content/exhibits`. Its filename is its permanent path: `content/exhibits/html-over-dns.md` becomes `/exhibits/html-over-dns`.

```md
---
title: HTML over DNS
summary: A web page rendered from DNS answers.
category: Tools and toys
tags: web, TXT records
image: /static/images/talk/slide-38.webp
source: https://example.org
status: published
updated: 2026-08-20T00:00:00Z
---

Markdown body here.
```

Use `status: draft` to keep an exhibit in the curator desk until it is ready. The admin editor writes these Markdown files and provides a live page preview. It can publish or delete an exhibit. It accepts JPEG, PNG, and WebP slide images up to 2 MB. It stores the original and a 480-pixel WebP card thumbnail in `content/uploads`. It stores screenshots pasted into exhibit content in `content/uploads/body`. The front controller serves these files through `/static/images/uploads/`. Visitors can fetch only images that a published exhibit references.

Slide images in `public/static/images/talk` are optimised WebP copies of selected source slides. Their card thumbnails are in `public/static/images/talk/thumbnails`. Record the original source in the exhibit; do not add third-party assets without recording their source.

## Production deployment

Serve the site through HTTPS with a web server such as nginx or Caddy and PHP-FPM. Set the document root to the project's `public` directory and route PHP requests through the front controller. Set a strong curator password and `MUSEUM_SECURE_COOKIES=true` in the server's `.env` file. Do not commit `.env` or other secrets. Keep the PHP front controller behind the web server; do not expose PHP-FPM directly.

Anonymous GET and HEAD requests for public pages can use a 60-second public cache. Requests with a session cookie, requests to `/admin`, and other requests use `private, no-store`. Missing or draft exhibits and server errors also use `private, no-store`. The login endpoint stores failed-attempt timestamps in a lock-protected file in the private, mode-0700 `content/.state` directory. The directory must be owned by the PHP-FPM user. It records up to five failures per source address in a rolling 15-minute window, then returns HTTP 429 with `Retry-After` on later attempts. Allow PHP-FPM to create and write `content/.state`; retain it across requests. The application uses the immediate peer address supplied by the web server and does not trust `X-Forwarded-For`. With direct Caddy, this is the client address; if another proxy sits in front of Caddy, configure trusted proxy handling at Caddy and confirm the FastCGI `REMOTE_ADDR` value. This avoids accepting a client-controlled forwarding header as an identity.

Changing `MUSEUM_ADMIN_PASSWORD` invalidates existing curator sessions on their next request. No service restart is needed if PHP-FPM reads the `.env` file on each request, as this application does.

Deploy a reviewed, version-controlled commit and push the deployed DNS Museum changes to GitHub as part of every live deployment. Keep the server's `.env`, `content/exhibits`, and `content/uploads` files when deploying code. Back up editorial content before making changes.

Give PHP-FPM write access only to `content/exhibits`, `content/uploads`, and its `thumbnails` and `body` subdirectories. Slide image uploads need ImageMagick with WebP support to create card thumbnails. Set `MUSEUM_IMAGE_MAGICK` in `.env` if the `convert` binary is not at its default location, `/usr/bin/convert`.

### Migrate existing uploads

Pause curator edits during the migration. Back up `public/static/images/uploads` outside the document root. Move that directory to `content/uploads` with its `thumbnails` and `body` subdirectories. If `content/uploads` already exists, check for name conflicts and preserve both sets of files before moving them. Keep the stored URLs in exhibit Markdown. Static slides under `public/static/images/talk` stay in place.

Ensure requests for `/static/images/uploads/*` reach `public/index.php` before any static file handler. The repository nginx configuration sets this explicitly. With Caddy, verify that `php_fastcgi` sends this prefix to the front controller when no file exists under the document root. Verify that no legacy copies remain under the public path. If a shared cache served the old upload URLs, purge those cached responses. Verify that a published upload works without a session and that a draft upload returns 404 without a session and 200 with a curator session.
