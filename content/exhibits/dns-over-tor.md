---
title: DNS over Tor
summary: Cloudflare documents an experimental DNS-over-HTTPS resolver with its own Tor onion address.
category: DNS over something else
tags: Tor, onion service, DNS over HTTPS, Cloudflare
image:
source: https://developers.cloudflare.com/1.1.1.1/additional-options/dns-over-tor/
status: published
updated: 2026-11-27T00:00:00Z
---

Cloudflare gave its resolver an onion address so a Tor client can send it DNS-over-HTTPS queries without leaving the Tor network through an exit relay. Both the client and the resolver connect through Tor.

Cloudflare describes the onion service as experimental and advises against relying on it for critical lookups.
