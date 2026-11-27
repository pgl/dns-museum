---
title: Software update checks over DNS
summary: GopherWatch publishes Go module and toolchain versions in DNS.
category: Tools and toys
tags: software updates, Go, GopherWatch
image:
source: https://archive.fosdem.org/2026/schedule/event/WWBVAT-querying_dns_for_software_updates/
status: published
updated: 2026-11-27T00:00:00Z
---

When a developer publishes a new version of a Go module, GopherWatch can spot the release in the Go checksum database. It makes the latest version available through DNS, so a program can check for updates with a lookup. Go toolchain versions are available the same way.

Mechiel Lukkien described the service in a talk at FOSDEM in 2026.
