---
title: FTP over DNS
summary: FToDNS adapts file-transfer exchanges to DNS queries and answers, splitting data across a transport with strict size and timing limits.
category: Tunnelling
tags: FTP, tunnelling, file transfer
image: /static/images/talk/slide-45.webp
source: https://github.com/neitzert/FToDNS
status: published
updated: 2026-08-20T00:00:00Z
---

FToDNS adapts FTP-style file transfer to DNS transport. The project turns the small pieces available in DNS traffic into a path for moving files.

It sits beside HTTP, IP, and VPN examples as another answer to the same question: what happens when DNS is the only usable channel?
