---
title: Leap seconds in DNS
summary: Tony Finch published the leap-second table in several DNS record formats.
category: Tools and toys
tags: leap seconds, time, DNSSEC
image:
source: https://github.com/fanf2/leapsecs
status: published
updated: 2026-11-27T00:00:00Z
---

At `leapsecond.dotat.at`, the record type determines how to read the time data. The TXT form counts the months between leap seconds. The AAAA form packs dates and times into fields normally used for IPv6 addresses. Finch signed the lists so readers could check them. [Finch's guide to the formats](https://dotat.at/writing/time.html)

The name returned a TXT answer on 27 November 2026.
