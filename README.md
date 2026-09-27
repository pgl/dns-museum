# DNS Museum

Original content by [Peter Lowe](https://github.com/pgl): [CC BY 4.0](LICENSE) · [About the project](https://dns.museum/about/).

DNS Museum is a small, Markdown-backed field guide to unusual uses of the Domain Name System. It starts with entries from [Peter Lowe](https://github.com/pgl)'s **Bizarre and Unusual Uses of DNS** talk and is designed to grow into a public reference, including a future, clearly separated DNS-abuse collection.

## Run locally with nginx

```sh
cp .env.example .env
brew install nginx
cp nginx/dns-museum.conf /opt/homebrew/etc/nginx/servers/dns-museum.conf
brew services start nginx
```

Open `http://127.0.0.1:8081`. The public collection is available at `/`; sign in at `/admin`.

Read `/about/` for the story behind the collection and its creator.

Set `MUSEUM_ADMIN_PASSWORD` in `.env` before using the curator desk. Set `MUSEUM_SECURE_COOKIES=true` when the site uses HTTPS.

## Content model

Each exhibit is one Markdown file in `content/exhibits`. Its filename is its permanent path: `content/exhibits/html-over-dns.md` becomes `/exhibits/html-over-dns`.

The required front matter is deliberately simple:

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

Use `status: draft` to keep an exhibit in the curator desk until it is ready. The editor uses the same fields, writes the Markdown files, previews unsaved content, and can publish or delete an exhibit. Upload a JPEG, PNG, or WebP slide image up to 2 MB to save the original and a server-generated 640-pixel WebP card thumbnail under `public/static/images/uploads`.

## Deployment

The app uses PHP-FPM behind nginx. Serve it through HTTPS in production, set a strong curator password, and run it from a checkout where the PHP-FPM user can write only `content/exhibits`. Back up that directory or push changes to GitHub after editorial updates.

Slide images are optimised WebP copies of selected source slides in `static/images/talk`; their card thumbnails are in `static/images/talk/thumbnails`. The original talk remains the source of attribution; do not add third-party assets without recording their source in the exhibit. PHP-FPM needs ImageMagick with WebP support and write access to `public/static/images/uploads` and its `thumbnails` subdirectory. Set `MUSEUM_IMAGE_MAGICK` in `.env` if the `convert` binary is not at `/usr/bin/convert`.

## License

Unless a work states otherwise, original written and visual content by [Peter Lowe](https://github.com/pgl) is licensed under the [Creative Commons Attribution 4.0 International License (CC BY 4.0)](https://creativecommons.org/licenses/by/4.0/). The full license text is in [LICENSE](LICENSE). Third-party content, including credited source material, remains under its own terms and is not covered by this license.
