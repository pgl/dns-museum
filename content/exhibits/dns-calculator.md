---
title: DNS calculator
summary: A Reverse Polish calculator accepts operands in a DNS name and returns a dynamically computed answer, a small service you can query with dig.
category: Tools and toys
tags: calculator, TXT records, utilities
image: /static/images/talk/slide-26.webp
source: https://www.cambus.net/interesting-dns-hacks/
status: published
updated: 2026-08-20T00:00:00Z
---

The Postel.org example is a Reverse Polish notation calculator. A query name carries the operands and operation; the authoritative server calculates the result and returns it dynamically.

The input and output are small enough to inspect with `dig`, but the design is best understood as a protocol experiment rather than a general-purpose calculator.
