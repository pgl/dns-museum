---
title: Images in a DNS answer
summary: David Leadbeater put an entire image in a single DNS TXT record.
category: Tools and toys
tags: images, TXT records, TCP, browser
image:
source: https://dgl.cx/2025/09/images-over-dns
status: published
updated: 2026-11-27T00:00:00Z
---

A single string in a DNS TXT record can hold 255 bytes. David Leadbeater demonstrated in 2025 how much more a record can hold by dividing a picture into several strings in the same record. His server sent the large answer over TCP.

In the web demo, the browser retrieved the record through Google's DNS-over-HTTPS API and reassembled the bytes into an image. One example was a picture of a dog.
