---
title: DeCSS over DNS
summary: In 2000, a series of DNS lookups could retrieve the source code of a DVD descrambler.
category: Other things
tags: DeCSS, source code, activism
image:
source: https://decss.zoy.org/
status: published
updated: 2026-11-27T00:00:00Z
---

DeCSS could decrypt DVD video protected by the Content Scramble System. During the fight over its distribution, one site listed 42 ways to get the program. DNS was one of them.

The publisher compressed the source file and encoded pieces of it in domain names. Readers could collect those names from a series of DNS answers and put the file back together. The records had a one-year time to live, so a resolver could keep serving its cached copy long after the first lookup.
