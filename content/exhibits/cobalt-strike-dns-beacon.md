---
title: Cobalt Strike DNS Beacon
summary: The first DNS Beacon used a DNS reply to decide when to fetch instructions over HTTP.
category: Tunnelling
tags: Cobalt Strike, Beacon, command and control, security
image:
source: https://www.cobaltstrike.com/blog/dns-communication-is-a-gimmick?p=2655
status: published
updated: 2026-11-27T00:00:00Z
---

Raphael Mudge released Beacon in September 2012 as part of Cobalt Strike, a security testing tool. In its original DNS mode, Beacon periodically asked for an address record. The reply told it whether there was a task waiting. If there was, Beacon fetched the task over HTTP.

Mudge later added the option to send the instructions themselves in DNS replies, using A or TXT records.
