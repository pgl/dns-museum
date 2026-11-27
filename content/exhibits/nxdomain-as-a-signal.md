---
title: NXDOMAIN as a signal
summary: Firefox and the Spamhaus Domain Blocklist each give a missing DNS name a specific meaning.
category: Other things
tags: NXDOMAIN, Firefox, DNS over HTTPS, canary
image:
source: https://support.mozilla.org/en-US/kb/canary-domain-use-application-dnsnet
status: published
updated: 2026-11-27T00:00:00Z
---

Before Firefox turns on DNS over HTTPS by default, it can ask the system resolver for `use-application-dns.net`. A network administrator can configure the resolver to answer NXDOMAIN for that one name. Firefox then keeps using the network's DNS settings. This lets a network keep features such as DNS filtering.

Firefox also treats a server error or a successful reply with no IPv4 or IPv6 address as a negative answer. A user who explicitly turns on DNS over HTTPS overrides this check.

The Spamhaus Domain Blocklist uses NXDOMAIN to answer a different question. To check whether it lists `example.com`, a mail filter looks up `example.com.dbl.spamhaus.org`. In the documented example, NXDOMAIN tells the filter that the list has no entry for `example.com`. [Spamhaus test queries](https://www.spamhaus.org/blocklists/domain-blocklist/)
