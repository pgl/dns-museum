---
title: Euro 2020 traceroute
summary: A traceroute display used live Euro 2020 scores, pairing a controlled route with DNS names to turn network diagnostics into a changing scoreboard.
category: Traceroutes
tags: traceroute, sport, hostname art
image: /static/images/talk/slide-9.webp
source: https://github.com/sehaas/fakert
status: published
updated: 2026-08-20T00:00:00Z
---

Sebastian Haas’s Euro 2020 example used a controlled route and DNS names to show live football scores in traceroute output. His `fakert` library can create a local tunnel device and static routes for experiments like this.

The changing score gives the familiar hop list a live data source; reverse-DNS labels provide the display.
