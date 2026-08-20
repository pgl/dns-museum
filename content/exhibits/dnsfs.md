---
title: DNSFS cache
summary: A filesystem-style concept that uses DNS answers as an intermediate cache.
category: Other things
tags: filesystem, cache, resolver
image: /static/images/talk/slide-56.webp
source: https://docs.google.com/presentation/d/1bRxNC3LNr-t7YYcOVl-aOHKL1llHNWRDxwpNFjIHCFo/edit
status: published
updated: 2026-08-20T00:00:00Z
---

The sketch treats DNS as a path from a filename-like query through an open resolver and into a cache. It is a useful lens on the fact that caching is built into how DNS works.

The cache is shared infrastructure, so it is unsuitable for private data and must be considered alongside resolver policy.
