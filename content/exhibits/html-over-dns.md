---
title: HTML over DNS
summary: A web client retrieves page content from DNS answers while a conventional server supplies the surrounding page, blurring lookup and content delivery.
category: Tools and toys
tags: HTML, web, TXT records
image: /static/images/talk/slide-38.webp
source: https://news.ycombinator.com/item?id=28218406
status: published
updated: 2026-08-20T00:00:00Z
---

This experiment uses a web page as a client for DNS-hosted content. The browser still loads a conventional shell, but the page body comes from DNS answers.

It blurs the line between naming service and content platform. It also shows why careful limits, caching, and escaping matter when DNS becomes an application backend.
