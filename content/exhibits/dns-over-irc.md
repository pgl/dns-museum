---
title: DNS over IRC
summary: Dynamic DNS updates carried through Internet Relay Chat.
category: DNS over something else
tags: IRC, dynamic DNS, updates
image: /static/images/talk/slide-18.webp
source: https://purpleidea.com/blog/2020/09/01/inexpensive-dns-over-irc/
status: published
updated: 2026-08-20T00:00:00Z
---

This design uses IRC as the transport for dynamic DNS updates. It repurposes an existing chat network for a task that normally needs a direct update channel.

It is unusual because the DNS data plane and the update transport are deliberately separate. DNS remains the published result; IRC carries the change requests.
