---
title: DNS as a content delivery network
summary: Two experiments let DNS resolvers cache the contents of web pages and files.
category: Other things
tags: CDN, caching, files, TXT records
image:
source: https://blog.zorinaq.com/cdn53-a-super-distributed-cdn/
status: published
updated: 2026-11-27T00:00:00Z
---

Marc Bevand's CDN53 experiment let a Chrome browser load a web page from DNS in 2014. A browser extension mapped the URL to a DNS name, and a local helper fetched the TXT record containing the page. A resolver that cached the answer could serve the same content to the next reader.

In 2023, Tony Garnock-Jones tried this with larger files. His tool split a file into chunks, named each chunk after its hash and stored it in a TXT record. His demonstration let visitors download an image this way. The demonstration server has since closed. [Garnock-Jones's account of nscdn](https://eighty-twenty.org/2023/07/31/dns-as-a-cdn)
