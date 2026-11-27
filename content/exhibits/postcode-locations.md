---
title: Postal codes in DNS
summary: DNS records can look up postal codes, place names, coordinates and map links in several countries.
category: Tools and toys
tags: postal codes, postcodes, ZIP codes, TXT, LOC, URI, maps
image:
source: https://www.bortzmeyer.org/dns-code-postal-lonlat.html
status: published
updated: 2026-11-27T00:00:00Z
---

Several projects put postal code data in DNS. A query name contains a postal code or place name, and the answer carries the result in a DNS record.

In France, Stéphane Bortzmeyer's service uses TXT records for names and codes, LOC records for coordinates, and URI records for OpenStreetMap links. For example, `dig +short TXT 46000.cp.bortzmeyer.fr` returns `CAHORS`. A TXT query for `DUNKERQUE.cp.bortzmeyer.fr` returns three postal codes: 59140, 59240 and 59640. Querying `46000.cp.bortzmeyer.fr` for LOC gives a location, while a URI query gives a map link.

In Switzerland, `dig +short LOC 1204.zipdns.ch` returns coordinates for postal code 1204. In Germany, `dig +short TXT 93047.zipde.jpmens.net` returns `Regensburg`, and a TXT query for `regensburg.zipde.jpmens.net` returns its postal codes. A query for `bielefeld.zipde.jpmens.net` returns NXDOMAIN, a reference to the [Bielefeld conspiracy](https://en.wikipedia.org/wiki/Bielefeld_conspiracy), a German joke that the city does not exist.

The UK service [find.me.uk](https://find.me.uk/) documents a DNS lookup for postcode coordinates. For example, RG12 1QS becomes `rg121qs.find.me.uk`. On 27 November 2026, a LOC query for that name completed without an error but returned no LOC record. The French, Swiss and German examples above returned answers on the same date.
