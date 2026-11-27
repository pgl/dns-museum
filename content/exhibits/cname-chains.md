---
title: Long CNAME chains
summary: In a 2024 lookup, seven DNS aliases led to the address for Brother's Indian website.
category: Other things
tags: CNAME, aliases, resolvers, CDN
image:
source: https://indico.dns-oarc.net/event/51/contributions/1098/attachments/1058/2144/CNAME-20241023.pdf
status: published
updated: 2026-11-27T00:00:00Z
---

A CNAME record says that one name is an alias for another. If the target is also an alias, the resolver follows the chain until it finds the address or the lookup fails.

Kazunori Fujiwara and Akira Sato documented a lookup for `www.brother.in` in their 2024 DNS-OARC presentation. The answer contained seven CNAME records and one IPv4 address. On 27 November 2026, a lookup for the same name at `1.1.1.1` returned two CNAME records before the address.
