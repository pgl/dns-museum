---
title: UK postcodes in DNS
summary: find.me.uk documents a DNS lookup for the coordinates of a UK postcode.
category: Tools and toys
tags: postcodes, UK, LOC, maps
image:
source: https://find.me.uk/
status: published
updated: 2026-11-27T00:00:00Z
---

The find.me.uk map accepts a postcode in a web address. Its DNS interface puts the postcode at the other end of the name. For example, the postcode RG12 1QS becomes `rg121qs.find.me.uk`. A LOC query asks for the coordinates of that postcode.

On 27 November 2026, the lookup for RG12 1QS completed without an error but returned no LOC record.
