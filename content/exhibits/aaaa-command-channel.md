---
title: Commands in AAAA records
summary: A 2016 security demonstration used ordinary-looking IPv6 answers to carry instructions.
category: Tunnelling
tags: AAAA records, command and control, security
image:
source: https://isc.sans.edu/diary/Command+and+Control+Channels+Using+%22AAAA%22+DNS+Records/21301
status: published
updated: 2026-11-27T00:00:00Z
---

In a 2016 Internet Storm Center diary, Johannes Ullrich demonstrated that a DNS AAAA answer could have two meanings. A DNS tool displayed IPv6 addresses. A program built for the experiment interpreted the same data as commands.

For defenders investigating a suspicious lookup, the example shows why it matters which program requested the record and what that program did with the answer.
