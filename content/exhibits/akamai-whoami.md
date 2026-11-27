---
title: Akamai whoami
summary: Akamai’s whoami lookup shows which resolver contacted its DNS server.
category: Tools and toys
tags: Akamai, resolver, IP address, diagnostics
image:
source: https://www.akamai.com/blog/developers/introducing-new-whoami-tool-dns-resolver-information
status: published
updated: 2026-11-27T00:00:00Z
---

The TXT record at `whoami.ds.akahelp.net` reports the address Akamai saw when the query reached its authoritative server. This lets someone using a shared resolver see the address the resolver used to send the query.

The answer can also show the client subnet if the resolver supplied one through EDNS Client Subnet. The example name returned a TXT answer on 27 November 2026.
