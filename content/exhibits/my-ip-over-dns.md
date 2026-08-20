---
title: My IP over DNS
summary: A resolver query that reports the client address it sees.
category: Tools and toys
tags: client IP, resolver, TXT records
image: /static/images/talk/slide-27.webp
source: https://www.cambus.net/interesting-dns-hacks/
status: published
updated: 2026-08-20T00:00:00Z
---

“What is my IP?” is often a web-page task. DNS services from OpenDNS and Google also made it available through a resolver query.

The answer can differ from the address a device expects because it reports the address visible to the resolver. That makes it a useful diagnostic as well as a neat DNS trick.
