---
title: HTTP over DNS
summary: A browser tunnel encodes arbitrary request strings in DNS subdomain labels and reconstructs them at the far end, showing the overhead of HTTP over DNS.
category: Tunnelling
tags: HTTP, tunnel, proof of concept
image: /static/images/talk/slide-44.webp
source: https://github.com/veggiedefender/browsertunnel
status: published
updated: 2026-08-20T00:00:00Z
---

HTTP is verbose, yet it can be encoded into a series of small DNS exchanges. The slide demonstrates the architecture and its limits.

This entry is intentionally descriptive. A future abuse section can add defensive indicators and response advice with the FIRST DNS Abuse SIG context.
