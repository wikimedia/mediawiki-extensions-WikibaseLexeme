High level PHP code organization of Wikibase Lexeme.

This document describes what the main directories are responsible for, what kinds of code they can contain and what
kinda of code they can bind to. Currently these restrictions are preliminary and they are often violated. This document
is thus more about where we want to go than accurately describing the current state.

## src/Domain

Contains business code. Cannot contain any presentation code, persistence bound code, framework bound code or code
specific to a single Interactor/UseCase.

* Model: Domain Model (Entities (DDD), Value Objects, Aggregates, Events, etc)
* Diff: diffing and patching of domain objects
* DummyObjects: null objects
* EntityReferenceExtractors:
* Storage: interfaces for persistence services and non-mechanism-bound implementations
* Merge:

## src/Interactors

Interactors are application code. One directory per interactor. Interactors cannot contain any presentation code,
persistence bound code or framework bound code or code. See the Use Cases section in the [Clean Architecture blog post](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html).

At the moment the dependency restrictions for Interactors are often violated. The idea is to work towards adhering to
these restrictions.

## src/DataAccess

Persistence service implementations. All code binding to persistence mechanisms (ie database, elastic search) should
reside here. In other words, no code outside this directory is allowed to know about persistence details like database
tables.

Framework binding is allowed here (though might still make sense to avoid in certain sets of code).

It likely makes sense to treat MediaWiki pages, revisions, etc, as persistence mechanism.

* ChangeOp:
* Search:
* Store: implementations of persistence services

## src/Presentation

All presentation mechanism (ie web, console) specific code goes here. This means that code dealing with HTML tags or
HTTP request parameters that is in another directory is misplaced. No domain logic should be invoked from presentation
code.

Framework binding is allowed here (though might still make sense to avoid in certain sets of code).

## src/MediaWiki

Implementation of MediaWiki hook mechanisms such as MediaWiki hooks, special pages and API modules.

Ideally these do not contain any application code and instead invoke an Interactor.

## src/Serialization

This has its own directory since it belongs to both presentation and persistence layers (which is somewhat odd).