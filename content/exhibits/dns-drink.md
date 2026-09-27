---
title: Drink DNS server
summary: Drink is Stéphane Bortzmeyer’s experimental authoritative DNS server: query different names and record types for dynamic answers such as greetings, client addresses, and diagnostics.
category: Tools and toys
tags: dynamic answers, DNSSEC, EDNS, TXT records
image: /static/images/talk/slide-34.webp
source: https://www.bortzmeyer.org/drink.html
status: published
updated: 2026-08-20T00:00:00Z
---

Drink is Stéphane Bortzmeyer’s experimental authoritative DNS server, named for no special reason. Its dynamic answers include greetings, the address seen by the authoritative server, date and time, random values, connection details, and weather reports.

The response can depend on the queried name, record type, and request context. Drink also demonstrates DNS features such as DNSSEC signing, EDNS options, and both UDP and TCP transport; it is an experiment, not a mission-critical service.
