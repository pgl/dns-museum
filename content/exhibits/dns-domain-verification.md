---
title: Domain verification through DNS
summary: A DNS record can show that a certificate applicant controls a domain.
category: Other things
tags: verification, certificates, ACME, TXT records
image:
source: https://letsencrypt.org/docs/challenge-types/
status: published
updated: 2026-11-27T00:00:00Z
---

For its DNS-01 challenge, Let's Encrypt gives the applicant a token. The applicant uses it with their ACME account key to calculate a value and publishes the result in a TXT record. For `example.org`, the certificate authority looks for that value at `_acme-challenge.example.org`.

Google Search Console uses a similar test before it gives someone access to a domain's search reports. [Google's verification guide](https://support.google.com/webmasters/answer/9008080?hl=en)
