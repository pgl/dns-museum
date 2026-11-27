---
title: Asking a name server for its identity
summary: Some name servers identify themselves through a TXT record in the CHAOS class.
category: Tools and toys
tags: CHAOS, name servers, diagnostics, TXT records
image:
source: https://datatracker.ietf.org/doc/html/rfc4892#section-2
status: published
updated: 2026-11-27T00:00:00Z
---

When several name servers share an IP address, it can be hard to tell which one answered. Some servers accept TXT queries for `hostname.bind` or `id.server` in the CHAOS class (`CH`). The operator chooses what the reply reveals. A related name, `version.bind`, can report the software version.

On 27 November 2026, `1.1.1.1` answered a CHAOS TXT query for `id.server`. As RFC 4892 points out, the identifier belongs to the server that answered that query. An earlier lookup at the same address may have reached a different server.
