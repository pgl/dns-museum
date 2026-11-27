---
title: Internationalised domain names before IDNA
summary: One early scheme for non-ASCII domain names used the prefix bq--.
category: Other things
tags: internationalised names, IDN, IDNA, history
image:
source: https://archive.icann.org/en/meetings/stockholm/idn-topic-survey-a.htm
status: published
updated: 2026-11-27T00:00:00Z
---

In a 2001 ICANN survey, developers described several ways to handle domain names written in Chinese or Japanese. Some systems sent names in UTF-8 or a local character encoding. Others converted each part of the name into ASCII before sending a DNS query.

One of those conversion schemes, RACE, marked each encoded label with `bq--`. In 2003 the IETF published the first standard for Internationalizing Domain Names in Applications (IDNA). It chose Punycode and the prefix `xn--`. [RFC 3490, section 5](https://www.rfc-editor.org/rfc/rfc3490.html#section-5)
