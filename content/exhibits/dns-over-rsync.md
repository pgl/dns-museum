---
title: DNS over rsync
summary: A directory listing from Koen van Hove’s rsync server doubled as a DNS answer.
category: DNS over something else
tags: rsync, resolver, experiments
image:
source: https://blog.koenvh.nl/what-you-can-but-shouldnt-do-with-rsync
status: published
updated: 2026-11-27T00:00:00Z
---

In December 2025, Koen van Hove demonstrated a resolver that accepted lookups through rsync. The path supplied the DNS record type and domain name. The matching records appeared in the directory listing as files.

Each file's modification time told the reader when the record's time to live would expire. [Van Hove's original forum post](https://gathering.tweakers.net/forum/list_messages/2101506/49)
