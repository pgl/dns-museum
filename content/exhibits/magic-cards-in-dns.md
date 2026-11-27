---
title: Magic cards in DNS
summary: MTGResolver puts a Magic card’s rules text, mana cost and card type in DNS.
category: Tools and toys
tags: Magic: The Gathering, cards, games, TXT records
image:
source: https://www.mtgresolver.com/
status: published
updated: 2026-11-27T00:00:00Z
---

MTGResolver gives each Magic card a DNS name. The project's example uses `lightning-bolt.card.mtgresolver.com`. A TXT lookup asks for all the card's details. Adding `mana.` to the front of the name asks just for the cost to cast it.

The server gets its card data from Scryfall.

The Lightning Bolt name returned a TXT answer on 27 November 2026.
