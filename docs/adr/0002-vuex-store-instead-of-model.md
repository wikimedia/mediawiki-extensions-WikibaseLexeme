# 2. Use vuex store instead of models {#adr_0002}

Date: 2018-07-02

## Status

accepted

## Context

Before using ADR we decided that we would use vue. Vue needs a form of state management to work with. State in vue can be persisted on a vue component level as raw data objects but with growing scale and complexity of the application the need to share this state between components, without additional safety measures, can lead to code that is hard to understand and/or debug.

There is an officially recommended state management implementation for vue [[0]] - Vuex; which is community-supported, free, and follows the renowned Flux pattern [[1]].

Vuex offers using a "single source of truth" for the application [[2]] with added guards. Only mutation through events are possible. This helps to protect from the down-sides of scattered code holding reference to the same objects.

Vuex ships with an existing best practice for structure [[3]] and out-of-the-box integration with debug tools. The nature of event-based state mutation provides benefits like ease of debugging ("time-travel") and promises maintainability in a growing application.

We already use Vuex within the WikibaseLexeme codebase. See: `resources/widgets/LexemeHeader.newLexemeHeaderStore.js`

There is also an existing approach within WikibaseLexeme to persist, pass and partially validate state using rich models (`wikibase.lexeme.datamodel.Lexeme` et al.). These were written to mimic the backend data model but did so incompletely. To accomodate the need for components in a (temporarily while editing) "dirty" state, another `wikibase.lexeme.LemmaList` kind of state was added pointing out the need for flexibility beyond the model's capability in this regard.

Losing object-oriented accessors and existing "validation" rules, for an apparent anemic model [[4]], could feel cumbersome at first [[5]] and consequences of actions are not always immediately obvious. But Vuex’s event-based store system provides us with an extensible interface for us and 3rd parties that offers flexibility, robustness, existing & well-maintained documentation.

## Decision

We will use Vuex as state management.

## Consequences

Newly developed components will be using this store exclusively.
Existing components will gradually be migrated to use this store.
Use of of rich models, e.g. wb.lexeme.datamodel.Lexeme, will be phased out.
There should be only one state, consequently `LexemeHeader.newLexemeHeaderStore` will be merged into `wikibase.lexeme.store`.


[0]: https://vuejs.org/v2/guide/state-management.html#Official-Flux-Like-Implementation
[1]: https://facebook.github.io/flux/
[2]: https://vuex.vuejs.org/guide/state.html
[3]: https://vuex.vuejs.org/guide/structure.html
[4]: https://martinfowler.com/bliki/AnemicDomainModel.html
[5]: https://vuex.vuejs.org/guide/state.html#the-mapstate-helper
