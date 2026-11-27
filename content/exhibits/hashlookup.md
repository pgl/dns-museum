---
title: CIRCL hash lookup
summary: CIRCL documents how to look up a file by its MD5 or SHA-1 hash in DNS.
category: Tools and toys
tags: hashes, CIRCL, TXT records, forensics
image:
source: https://www.circl.lu/services/hashlookup/
status: published
updated: 2026-11-27T00:00:00Z
---

CIRCL collects hashes of known files, including files from operating systems and software packages. Its DNS interface accepts an MD5 or SHA-1 hash as part of a domain name and returns a match in a TXT record. There is also an HTTP API.

In CIRCL's example, a lookup identifies a copy of the Unix program `ls` and gives its size in bytes. A match tells the investigator which file the database recognises.

On 27 November 2026, CIRCL's database information record at `info.dns.hashlookup.circl.lu` returned a TXT answer.
