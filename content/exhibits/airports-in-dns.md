---
title: Airports in DNS
summary: A three-letter airport code doubled as a DNS name for airport details, coordinates and maps.
category: Tools and toys
tags: airports, IATA, LOC, TXT records, maps
image:
source: https://jpmens.net/2020/10/04/airports-of-the-world/
status: published
updated: 2026-11-27T00:00:00Z
---

Jan-Piet Mens wanted airport data for a training exercise. In 2020 he took the public OurAirports dataset and gave each airport with an IATA code its own DNS name.

For Barcelona, his example uses `BCN.air.jpmens.net`. A TXT lookup gave the airport's name, city and country. A LOC lookup gave its coordinates. URI records linked to a map and, where available, a Wikipedia page. Mens also published the zone data for self-hosting.

The Barcelona name returned a TXT answer on 27 November 2026.
