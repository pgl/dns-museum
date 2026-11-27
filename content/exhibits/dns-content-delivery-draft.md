---
title: A fallback web page in DNS
summary: An Internet-Draft proposed storing a fallback web page in DNS.
category: Other things
tags: Internet-Draft, TXT records, HTML, DNSSEC
image:
source: https://datatracker.ietf.org/doc/draft-dns-content-delivery/
status: published
updated: 2026-11-27T00:00:00Z
---

In March 2026, April Faye John proposed that a browser could fetch HTML or JSON from a TXT record if it could not reach a website. A parked domain could use the same mechanism to show a landing page without running a web server.

Under the proposal, the browser would use DNSSEC to verify the record before displaying its contents. The individual Internet-Draft expired in September 2026.
