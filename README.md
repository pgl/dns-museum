# Museum of DNS

The Museum of DNS is a small, Markdown-backed field guide to unusual uses of the Domain Name System. It starts with exhibits from Peter Lowe's **Bizarre and Unusual Uses of DNS** talk and is designed to grow into a public reference, including a future, clearly separated DNS-abuse collection.

## Run locally

```sh
cp .env.example .env
set -a; source .env; set +a
go run ./cmd/museum
```

Open `http://127.0.0.1:8080`. The public collection is available at `/`; sign in at `/admin`.

Set `MUSEUM_ADMIN_PASSWORD` and a long, unique `MUSEUM_SESSION_SECRET` before deployment. Set `MUSEUM_SECURE_COOKIES=true` when the site uses HTTPS.

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

Use `status: draft` to keep an exhibit in the curator desk until it is ready. The editor uses the same fields, writes the Markdown files, previews unsaved content, and can publish or delete an exhibit.

## Deployment

The app is a single Go binary with no runtime dependencies. Serve it behind an HTTPS reverse proxy, set a strong session secret, and run it from a checkout where the service account can write only `content/exhibits`. Back up that directory or push changes to GitHub after editorial updates.

Slide images are optimised WebP copies of selected source slides in `static/images/talk`. The original talk remains the source of attribution; do not add third-party assets without recording their source in the exhibit.
