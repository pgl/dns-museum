---
title: DNS over email
summary: Cloudflare let people look up DNS records by sending an email and waiting for the reply.
category: DNS over something else
tags: email, Cloudflare, DNS lookups, resolver
image:
source: https://github.com/cloudflare/cloudflare-docs/blob/551248ebd58150ae878e6f8e1b39e933c854c5bf/products/1.1.1.1/src/content/fun-stuff/dns-over-email.md
status: published
updated: 2026-11-27T00:00:00Z
---

By May 2018, Cloudflare had a mailbox for DNS queries. If you sent `example.com` in the body of an email to `lookup@resolver.email`, the reply gave you the AAAA lookup result for that name. To ask for its mail servers instead, you could write `MX example.com`. [Cloudflare's 2018 tutorial](https://web.archive.org/web/20180524132330/https://developers.cloudflare.com/1.1.1.1/fun-stuff/dns-over-email/)

Cloudflare removed the tutorial in September 2021. The service's closing date is unknown. On 27 November 2026, both `1.1.1.1` and `1.0.0.1` returned NXDOMAIN when asked for the mail servers of `resolver.email`. [Documentation change](https://github.com/cloudflare/cloudflare-docs/commit/a7733f796f6b635c2cdadf8472248ac17c0ea803)
