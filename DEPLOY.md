# Development and deployment

## Run locally with nginx

```sh
cp .env.example .env
brew install nginx
cp nginx/dns-museum.conf /opt/homebrew/etc/nginx/servers/dns-museum.conf
brew services start nginx
```

Open `http://127.0.0.1:8081`. The collection is at `/`; sign in at `/admin`.

Set `MUSEUM_ADMIN_PASSWORD` in `.env` before using the curator desk. Set `MUSEUM_SECURE_COOKIES=true` when the site uses HTTPS.

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

Use `status: draft` to keep an exhibit in the curator desk until it is ready. The admin editor writes these Markdown files and provides a live page preview. It can publish or delete an exhibit. It accepts JPEG, PNG, and WebP slide images up to 2 MB and saves a 640-pixel WebP card thumbnail under `public/static/images/uploads`.

Slide images in `public/static/images/talk` are optimised WebP copies of selected source slides. Their card thumbnails are in `public/static/images/talk/thumbnails`. Record the original source in the exhibit; do not add third-party assets without recording their source.

## Production deployment

Serve the site through HTTPS with a web server such as nginx or Caddy and PHP-FPM. Set the document root to the project's `public` directory and route PHP requests through the front controller. Set a strong curator password and `MUSEUM_SECURE_COOKIES=true` in the server's `.env` file. Do not commit `.env` or other secrets.

Deploy a reviewed, version-controlled commit and push the deployed DNS Museum changes to GitHub as part of every live deployment. Keep the server's `.env`, `content/exhibits`, and `public/static/images/uploads` files when deploying code. Back up editorial content before making changes.

Give PHP-FPM write access only to `content/exhibits`, `public/static/images/uploads`, and its `thumbnails` subdirectory. Image uploads need ImageMagick with WebP support. Set `MUSEUM_IMAGE_MAGICK` in `.env` if the `convert` binary is not at its default location, `/usr/bin/convert`.
