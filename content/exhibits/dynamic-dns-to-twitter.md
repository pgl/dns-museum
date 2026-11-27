---
title: Twitter updates via dynamic DNS
summary: Jan-Piet Mens posted a tweet by putting its text in a DNS update.
category: Other things
tags: Twitter, dynamic DNS, RFC 2136, TXT records
image:
source: https://jpmens.net/2011/04/18/rfc-2136-ddns-updates-to-twitter/
status: published
updated: 2026-11-27T00:00:00Z
---

In 2011, Jan-Piet Mens used `nsupdate` to add a TXT record to his BIND server. A BIND driver passed the text to a Node.js program, which posted it to Twitter.

The tweet identified its client as “RFC 2136”, the number of the specification for dynamic DNS updates. Mens kept a screenshot and linked to the code in his write-up.
