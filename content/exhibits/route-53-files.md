---
title: Route 53 Files
summary: Colin Percival made Route 53 records editable as files on an NFS mount.
category: Other things
tags: Route 53, filesystem, AWS, NFS
image:
source: https://www.daemonology.net/blog/2026-08-27-Launching-Route-53-Files.html
status: published
updated: 2026-11-27T00:00:00Z
---

In August 2026, Colin Percival announced Route 53 Files, an independent project, in the style of an AWS product launch. It presents a hosted zone as an NFS filesystem. Each DNS name has a directory, each record set has a file, and an alias appears as a symbolic link to its target.

Saving a file updates Route 53. Editing a record in Route 53 changes what appears in the directory. Percival reported that a save took about 90 seconds to reach live DNS, before accounting for resolver caches.
