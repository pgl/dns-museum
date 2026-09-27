---
title: DNS weather
summary: The Drink DNS server returns current or next-day weather in TXT answers; encode a city and time in the queried name to retrieve the report.
category: Tools and toys
tags: weather, TXT records, utilities
image: /static/images/talk/slide-35.webp
source: https://x.com/LAB3W_ORJ/status/1719732574333837745
status: published
updated: 2026-08-20T00:00:00Z
---

Drink can return current or next-day weather as TXT data. The queried name includes a city, a time selector such as `now` or `tomorrow`, and the weather service label.

This makes DNS a compact interface to a changing dataset, while the answer’s TTL and resolver cache affect how quickly clients see updates.
