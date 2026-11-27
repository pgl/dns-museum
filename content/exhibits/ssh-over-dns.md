---
title: SSH over DNS
summary: Dan Kaminsky's OzymanDNS carried a remote login session in DNS queries and replies.
category: Tunnelling
tags: SSH, OzymanDNS, Dan Kaminsky, tunnelling
image:
source: https://events.ccc.de/congress/2004/fahrplan/files/297-black-ops-of-dns-slides.pdf
status: published
updated: 2026-11-27T00:00:00Z
---

At the 2004 Chaos Communication Congress, Dan Kaminsky showed how an SSH session could travel over DNS. He called the experimental toolkit OzymanDNS and its stream router `droute`.

SSH normally has a connection that either end can write to at any time. A DNS server can only reply to a query. To keep an interactive session going, the client had to keep asking for replies, even when the person at the keyboard had typed nothing.

The original SSH-over-DNS tutorial returned a 404 error in November 2026. The congress archive still has Kaminsky's slides.
