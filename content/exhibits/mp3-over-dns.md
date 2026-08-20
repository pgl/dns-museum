---
title: MP3 over DNS
summary: A tiny command that streams audio data through repeated DNS queries.
category: Other things
tags: audio, MP3, shell
image: /static/images/talk/slide-57.webp
source: https://gist.github.com/Manawyrm/718cf8ab6ba59ba95d9743d01b1763dd
status: published
updated: 2026-08-20T00:00:00Z
---

The exhibit is deliberately absurd: a shell pipeline requests encoded pieces, turns them back into bytes, and passes the result to an audio player.

It captures the spirit of the collection. DNS is not the ideal audio transport, but it is flexible enough to make the experiment possible.
