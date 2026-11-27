---
title: Reading tweets via DNS
summary: Dan Moore's gateway let readers look up the latest tweet from a Twitter account by name.
category: Tools and toys
tags: Twitter, TXT records, social media, gateway
image:
source: https://jpmens.net/2009/11/28/read-tweets-via-the-dns/
status: published
updated: 2026-11-27T00:00:00Z
---

In 2009, Jan-Piet Mens looked up the TXT record for `jpmens.twitter.any.io`. The reply contained his latest tweet and the time he posted it. Dan Moore's gateway used the Twitter account name as the first part of the DNS name.

On 27 November 2026, the resolver at `1.1.1.1` returned NXDOMAIN for the same name.
