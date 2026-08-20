---
title: DNS calculator
summary: Arithmetic results returned from DNS queries.
category: Tools and toys
tags: calculator, TXT records, utilities
image: /static/images/talk/slide-26.webp
source: https://www.cambus.net/interesting-dns-hacks/
status: published
updated: 2026-08-20T00:00:00Z
---

Several DNS zones have used TXT answers as a tiny command-line service. A calculator is a clear example because the input and output are small, textual, and easy to inspect with `dig`.

Availability changes over time, so treat these services as exhibits rather than production calculators.
