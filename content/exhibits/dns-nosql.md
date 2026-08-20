---
title: DNS NoSQL store
summary: A proof of concept that treats DNS as a distributed key-value data store.
category: Other things
tags: NoSQL, database, key-value
image: /static/images/talk/slide-50.webp
source: https://dyna53.io/
status: published
updated: 2026-08-20T00:00:00Z
---

Records in a DNS zone can be viewed as values reached through hierarchical keys. This experiment pushes that idea toward a generic data-store model.

The analogy is useful, but DNS has very different consistency, authority, cache, and size rules from a conventional database.
