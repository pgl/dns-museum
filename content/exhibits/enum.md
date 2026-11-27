---
title: Telephone numbers in DNS
summary: ENUM turns an international telephone number into a DNS name for finding services such as internet calls.
category: Other things
tags: ENUM, telephone, VoIP, NAPTR
image:
source: https://www.rfc-editor.org/rfc/rfc6116.html
status: published
updated: 2026-11-27T00:00:00Z
---

To look up a number with ENUM, a client drops the plus sign, reverses the digits, separates them with dots and adds `e164.arpa`. Putting the country code nearest the end of the name lets DNS delegate a country's part of the numbering plan.

If the number has an entry, its NAPTR records can direct the client to a service, such as a SIP address for an internet call. RFC 6116 describes the lookup.
