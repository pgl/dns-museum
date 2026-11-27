---
title: Bad Apple over DNS
summary: A player retrieves the Bad Apple!! animation one DNS lookup at a time.
category: Tools and toys
tags: Bad Apple, animation, TXT records
image:
source: https://github.com/nicewrld/badapplens
status: published
updated: 2026-11-27T00:00:00Z
---

The badapplens project starts with a local copy of the Bad Apple!! video and splits it into frames. A small DNS server supplies the frames, and a separate player requests them in sequence. Each lookup contributes one still image to the animation.

The project repository contains the converter, server and player.
