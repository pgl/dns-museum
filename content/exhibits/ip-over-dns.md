---
title: IP over DNS
summary: iodine builds an IP tunnel between two endpoints over DNS, so other IP traffic can use the link, with low throughput and DNS-shaped constraints.
category: Tunnelling
tags: tunnel, IP, transport
image: /static/images/talk/slide-43.webp
source: https://github.com/yarrick/iodine
status: published
updated: 2026-08-20T00:00:00Z
---

iodine creates a tunnel between two endpoints by carrying IP traffic through DNS queries and answers. Once the IP link exists, applications that use IP can use it too; the tunnel does not need a conventional DNS server running at either endpoint.

The link is slow and constrained by DNS message sizes, resolver behaviour, and caching. This is a protocol-history example, not a guide to bypassing network policy.
