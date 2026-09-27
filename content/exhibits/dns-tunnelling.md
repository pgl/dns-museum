---
title: DNS tunnelling
summary: DNS tunnelling encodes data in query names and answers to create a constrained communications channel, a pattern with both legitimate and harmful uses.
category: Tunnelling
tags: tunnelling, transport, record types
image: /static/images/talk/slide-42.webp
source: https://www.daemon.be/maarten/dnstunnel.html
status: published
updated: 2026-08-20T00:00:00Z
---

DNS tunnelling encodes data into query names and responses, then uses DNS as a transport channel. The idea was discussed publicly as early as 2000.

Many record types can carry pieces of a tunnel. The later entries in this section show what people built on top of that basic pattern.
