---
title: DNS key-value store
summary: DNSKV is a generic public key-value service: names identify keys and DNS answers return values, backed by caches distributed across recursive resolvers.
category: Other things
tags: key-value, database, TXT records
image: /static/images/talk/slide-54.webp
source: https://dnskv.com/
status: published
updated: 2026-08-20T00:00:00Z
---

DNSKV exposes a generic key-value interface through DNS names and answers. The talk highlights how recursive caches around the world can make repeated lookups fast and resilient.

Public DNS is not private storage: query data and answers pass through resolvers, and caching affects when updates become visible.
