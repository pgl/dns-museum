---
title: Wordle over DNS
summary: A Wordle-style game played entirely through DNS queries.
category: Tools and toys
tags: Wordle, game, DNS queries
image: /static/images/talk/slide-39.webp
source: https://dgl.cx/2022/02/wordle-over-dns
status: published
updated: 2026-08-20T00:00:00Z
---

This service adapts Wordle to DNS. Each query represents a guess, and the DNS response reports the game state.

It proves that a modern web-game format can work with a resolver and a terminal, provided the rules are expressed as short text.
