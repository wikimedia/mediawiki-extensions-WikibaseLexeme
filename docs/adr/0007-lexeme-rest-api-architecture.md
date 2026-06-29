# 1. Lexeme REST API architecture {#adr_0007}

Date: 2026-06-30

## Status

accepted

## Context

The Wikibase Reuse Team has been tasked with creating the Lexeme REST API. As the authors of the Wikibase REST API, we have already gathered considerable experience building this kind of API and have a set of architectural conventions we are confident in.

The WikibaseLexeme extension already has a sensible directory structure. It is not identical to the one we use in our other code bases, but it follows similar rules. Long-term ownership of the code base as a whole is also somewhat unclear, so we would prefer not to impose conventions that diverge from what is already there without good reason.

## Decision

We will generally follow the same approach for the Lexeme REST API as we did for the Wikibase REST API, including the Hexagonal Architecture pattern and the other [decisions taken during its development](https://doc.wikimedia.org/Wikibase/master/php/rest_adr_index.html). Rather than introducing a competing directory structure, we will keep the existing one and document how its directories map onto the structure we normally use.

## Consequences

The mapping between the existing directories and our usual structure will be documented in DIRECTORIES.md, and architecture tests will enforce the dependency rules implied by the Hexagonal Architecture pattern. The remaining, pre-existing code will be left as is rather than restructured to match.
