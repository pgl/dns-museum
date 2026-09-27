---
title: Blogging via DNS
summary: txtrex stores blog posts in TXT records: query one name to list posts and another to retrieve a post, making publishing possible with DNS alone.
category: Tools and toys
tags: blogging, TXT records, publishing
image: /static/images/talk/slide-36.webp
source: https://github.com/hiway/txtrex
status: published
updated: 2026-08-20T00:00:00Z
---

txtrex publishes posts as DNS TXT records. One query lists available posts; another retrieves a post’s text.

That makes the publishing path easy to inspect with DNS tools, but it inherits the size limits, cache behaviour, and update delay of DNS.
