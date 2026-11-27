---
title: ipasn.net
summary: Geoff Huston’s ipasn.net looks up the network that announces an IP address.
category: Tools and toys
tags: ipasn, ASN, BGP, routing
image:
source: https://ispcol.potaroo.net/2026-02/ipasn.html
status: published
updated: 2026-11-27T00:00:00Z
---

Given an IP address, ipasn.net finds the matching prefix in a snapshot of the BGP routing table and identifies the autonomous system that announces it. Other fields come from registry data and routing authorisations published through RPKI.

Huston's 2026 article shows how to request different parts of the result. On 27 November 2026, a TXT query for `8.8.8.8.ipasn.net` returned an answer.
