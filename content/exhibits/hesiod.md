---
title: Hesiod
summary: At MIT, a workstation could ask DNS where to find a user’s home directory or mailbox.
category: Other things
tags: Hesiod, MIT, Athena, directory, TXT records
image:
source: https://www.rfc-editor.org/rfc/rfc1035.html
status: published
updated: 2026-11-27T00:00:00Z
---

MIT's Project Athena used Hesiod as a shared directory. Given a username, a workstation could find the user's home directory, login shell or mail server. Athena stored each kind of information under a separate DNS name. [Examples from MIT](https://web.mit.edu/cluedumps/slides/how-athena-works.pdf)

Hesiod even has its own DNS class. RFC 1035 assigns it class 4, abbreviated HS.
