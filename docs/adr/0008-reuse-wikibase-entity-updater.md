# 8) Reuse Wikibase's EntityUpdater for storing Lexemes {#adr_0008}

Date: 2026-08-12

## Status

accepted

## Context

The Lexeme REST API needs to store Lexemes. Wikibase's CRUD domain has the `EntityUpdater` storage service that handles concerns like permissions, rate limits, entity size limits, temp account creation and such. It operates on `EntityDocument` and is therefore generic enough to work with Lexemes as is.

Reusing it means a Lexeme domain service implementation depends on another domain's infrastructure, which the cross-domain dependency rules following from [ADR 25](https://doc.wikimedia.org/Wikibase/master/php/adr_0025.html) and [ADR 7](@ref adr_0007) discourage. The alternative is to reimplement the same edit handling inside WikibaseLexeme.

## Decision

We will implement `LexemeCreator` based on the CRUD domain's `EntityUpdater`, and accept the cross-domain dependency as a deliberate, temporary exception.

ADR 25 anticipates that domain service implementations use a shared storage mechanism, so what is wrong here is not the sharing itself but the place it is shared from and the exact interface. The debt is low-risk in that it is unlikely to grow: the service is already generic, and is expected to need at most a minor adjustment to support Lexeme edit summaries.

## Consequences

The Lexeme storage implementations bind to the CRUD domain's `EntityUpdater`. The dependency is confined to a single class, so the rest of the Lexeme code is unaffected and the implementation can be swapped later. If and when a shared storage service is extracted into a more suitable location in Wikibase ([T434770](https://phabricator.wikimedia.org/T434770)), this implementation should move to it.
