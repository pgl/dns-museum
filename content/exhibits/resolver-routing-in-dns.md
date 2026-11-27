---
title: Resolver routing information in DNS
summary: Stéphane Bortzmeyer’s Drink server looks up the network behind a DNS query.
category: Tools and toys
tags: Drink, resolver, BGP, ASN
image:
source: https://www.bortzmeyer.org/drink.html
status: published
updated: 2026-11-27T00:00:00Z
---

Ask Drink for the TXT record at `bgp.dyn.bortzmeyer.fr` and it replies with three values. They identify the IP address it saw, the network prefix containing that address, and the autonomous system that announced the prefix.

A query through a recursive resolver therefore describes the resolver's network. The example name returned a TXT answer on 27 November 2026.
