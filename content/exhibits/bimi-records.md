---
title: BIMI records
summary: BIMI uses DNS records to point mail systems to brand logos; the talk notes that DMARC must be configured before the logo display can be used.
category: Other things
tags: BIMI, email, TXT records, brands
image: /static/images/talk/slide-51.webp
source: https://bimi.agari.com/
status: published
updated: 2026-08-20T00:00:00Z
---

Brand Indicators for Message Identification publishes brand information beneath `_bimi` as TXT records. Mail clients can use this data when they decide how to display a sender’s identity.

It is a more formal example of DNS as a metadata layer: the record is not a host address but a statement about brand presentation and mail policy.
