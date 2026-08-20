---
title: DNS key-value store
summary: A public interface for storing and retrieving values from a DNS domain.
category: Other things
tags: key-value, database, TXT records
image: /static/images/talk/slide-54.webp
source: https://docs.google.com/presentation/d/1bRxNC3LNr-t7YYcOVl-aOHKL1llHNWRDxwpNFjIHCFo/edit
status: published
updated: 2026-08-20T00:00:00Z
---

The service shows a literal key-value model: form a name for the key, then recover the value from DNS. It is compact and intuitive enough to demonstrate the pattern to anyone with a resolver.

Do not place confidential information in public DNS. Caching and replication are features here, not privacy controls.
