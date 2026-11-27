---
title: Run your own IP lookup in DNS
summary: Bob Rudis wrote a DNS server that tells the caller which IP address it sees.
category: Tools and toys
tags: extip-svr, IP address, diagnostics
image:
source: https://github.com/hrbrmstr/extip-svr
status: published
updated: 2026-11-27T00:00:00Z
---

Bob Rudis built extip-svr as a companion to his external IP lookup tool. In his example, the client sends its query directly to the server and asks for a TXT answer. That works for both IPv4 and IPv6.

The server reports the address that sent it the query. A direct lookup shows the client's outward-facing address. A lookup forwarded by a recursive resolver shows the resolver's address.
