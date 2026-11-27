---
title: DNS in Google Sheets
summary: A Google Sheets formula can put a DNS answer in a spreadsheet cell.
category: DNS over something else
tags: Google Sheets, spreadsheet, DNS over HTTPS
image:
source: https://developers.cloudflare.com/1.1.1.1/additional-options/dns-in-google-sheets/
status: published
updated: 2026-11-27T00:00:00Z
---

Cloudflare publishes a Google Apps Script function called `NSLookup`. Once it is installed, a spreadsheet can keep a column of domain names next to a column of DNS results.

For example, `=NSLookup("MX", A2)` asks for the mail servers of the domain in cell A2. The script sends the query to Cloudflare over HTTPS and puts the answer in the sheet.
