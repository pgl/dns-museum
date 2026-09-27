---
title: IPv6 hop expansion
summary: An IPv6 traceroute uses reverse-DNS names to draw a hand; raising the hop limit reveals more of the picture one numbered hop at a time.
category: Traceroutes
tags: traceroute, IPv6, routing
image: /static/images/talk/slide-8.webp
source: https://github.com/sehaas/fakert
status: published
updated: 2026-08-20T00:00:00Z
---

The `hand.bb0.nl` example uses IPv6 and reverse-DNS names to draw a hand as the route advances. Raising the traceroute hop limit reveals more numbered hops and therefore more of the image.

The effect depends on the chosen path and the PTR names returned for its addresses; it is a visual use of reverse DNS, not a new traceroute protocol.
