---
title: NUM contacts database
summary: NUM built a DNS data protocol with an SDK and npm modules; its Yellow Pages example retrieves structured company contacts through public resolver infrastructure.
category: Other things
tags: contacts, NUM, companies, database
image: /static/images/talk/slide-53.webp
source: https://www.numprotocol.com/
status: published
updated: 2026-08-20T00:00:00Z
---

NUM presented a complete DNS-based data protocol with an SDK, developer documentation, and npm modules. Its example exposed UK Yellow Pages company records, including details such as addresses and telephone numbers.

The talk also raised a design trade-off: the service benefits from other operators’ authoritative servers and recursive resolver caches. DNS makes the lookup fast and widely available, but the data path depends on shared infrastructure.
