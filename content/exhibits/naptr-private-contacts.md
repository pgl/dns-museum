---
title: Private contacts in DNS
summary: The .tel directory let people share a phone number through DNS with a chosen contact.
category: Other things
tags: NAPTR, .tel, contacts, privacy
image:
source: https://shkspr.mobi/blog/2025/08/preventing-naptr-spam/
status: published
updated: 2026-11-27T00:00:00Z
---

The original .tel directory let people put contact details in DNS and update them when they changed. To share a phone number privately, the owner could encrypt it with a friend's public key and give that friend the name of a new NAPTR record. The friend's address book could check the record for updates and decrypt it with the matching private key.

Terence Eden revisited the design in 2025. The registry had opened .tel domains to ordinary web hosting in 2017.
