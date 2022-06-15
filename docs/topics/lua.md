# Lua

WikibaseLexeme provides a Lua [Scribunto](https://www.mediawiki.org/wiki/Scribunto) interface that implements functions to access data from the Wikibase repository, if the Wikibase Client configuration enables `allowDataTransclusion` and `$wgLexemeEnableDataTransclusion`. Lua modules and wiki templates can invoke these functions.

Changes to the WikibaseLexeme Lua interface are subject to the [Stable Interface Policy](https://www.wikidata.org/wiki/Wikidata:Stable_Interface_Policy).

Most aspects of the data are accessed as methods on a loaded entity.
For Forms and Senses, you can either load the Form or Sense directly by its ID from `mw.wikibase.getEntity()`,
or load the surrounding Lexeme and then get the Form or Sense from it via `entity:getForms()` or `entity:getSenses()`.

<span style="color: red;">Accessing data of Lexemes is [expensive](https://www.mediawiki.org/wiki/Manual:$wgExpensiveParserFunctionLimit).</span>
Loading entities doesn't count as expensive if the same entity is loaded twice during a module run.
However, due to restrictions in the caching, if more than 14 other entities are loaded inbetween, the entity must be fetched again, which then counts as expensive.

## mw.wikibase.lexeme {#mw_wikibase_lexeme}
`mw.wikibase.lexeme` has some general Lua functionality for working with Wikibase Lexeme data.

### mw.wikibase.lexeme.splitLexemeId {#mw_wikibase_lexeme_splitLexemeId}
`mw.wikibase.lexeme.splitLexemeId( id )`

Split a Lexeme, Sense or Form ID into the Lexeme ID part
and (if present) Sense or Form ID part.
Returns two strings for Sense or Form IDs,
one string for Lexeme IDs and nil otherwise.

An example call might look like this:
```lua
l, s = mw.wikibase.lexeme.splitLexemeId( 'L1-S1' ) -- returns 'L1' and 'S1'
```

## mw.wikibase.lexeme.entity.lexeme {#mw_wikibase_lexeme_entity_lexeme}
`mw.wikibase.lexeme.entity.lexeme` has methods for accessing data of a loaded Lexeme entity.
It is typically not used directly – `mw.wikibase.getEntity( lexemeId )` returns a table on which you can call the following methods,
as well as the methods from [mw.wikibase.entity][].

### mw.wikibase.lexeme.entity.lexeme:getLemmas {#mw_wikibase_lexeme_entity_lexeme__getLemmas}
`entity:getLemmas()`

Gets the lemma(s) of this Lexeme,
as a list of tables where each table has the lemma text as the first element and the lemma language as the second.

An example call might look like this:
```lua
mw.wikibase.getEntity( 'L1' ):getLemmas() -- Returns { { 'ama', 'mis-x-Q36790' }, { '𒂼', 'mis-x-Q401' } } on Wikidata
```

## mw.wikibase.lexeme.entity.lexeme:getLemma {#mw_wikibase_lexeme_entity_lexeme__getLemma}
`entity:getLemma()`  
`entity:getLemma( languageCode )`

Gets the lemma of this Lexeme in the given language,
or in the content language if no language is given.
Returns the lemma and its language as two strings if the lexeme has a lemma in this language, or nil otherwise.
(Language fallbacks are not applied.)

An example call might look like this:
```lua
mw.wikibase.getEntity( 'L1' ):getLemma( 'mis-x-Q36790' ) -- Returns 'ama', 'mis-x-Q36790' on Wikidata
mw.wikibase.getEntity( 'L99' ):getLemma() -- Returns 'Luftballon', 'de' on German Wiktionary
```

### mw.wikibase.lexeme.entity.lexeme:getLanguage {#mw_wikibase_lexeme_entity_lexeme__getLanguage}
`entity:getLanguage()`

Gets the Item ID of the language of this Lexeme.

An example call might look like this:
```lua
mw.wikibase.getEntity( 'L1' ):getLanguage() -- Returns 'Q36790' on Wikidata
```

### mw.wikibase.lexeme.entity.lexeme:getLexicalCategory {#mw_wikibase_lexeme_entity_lexeme__getLexicalCategory}
`entity:getLexicalCategory()`

Gets the Item ID of the lexical category of this Lexeme.

An example call might look like this:
```lua
mw.wikibase.getEntity( 'L1' ):getLexicalCategory() -- Returns 'Q1084' on Wikidata
```

### mw.wikibase.lexeme.entity.lexeme:getForms {#mw_wikibase_lexeme_entity_lexeme__getForms}
`entity:getForms()`

Get the Forms of this Lexeme, as a list of Form entities.

An example call might look like this:
```lua
mw.wikibase.getEntity( 'L1' ):getForms() -- Returns a table of Form entities
```

### mw.wikibase.lexeme.entity.lexeme:getSenses {#mw_wikibase_lexeme_entity_lexeme__getSenses}
`entity:getSenses()`

Get the Senses of this Lexeme, as a list of Sense entities.

An example call might look like this:
```lua
mw.wikibase.getEntity( 'L1' ):getSenses() -- Returns a table of Sense entities
```

## mw.wikibase.lexeme.entity.form {#mw_wikibase_lexeme_entity_form}
`mw.wikibase.lexeme.entity.form` has methods for accessing data of a loaded Form entity.
It is typically not used directly –
`mw.wikibase.getEntity( formId )` returns a table on which you can call the following methods,
as well as the methods from [mw.wikibase.entity][],
while [lexeme:getForms()](#mw_wikibase_lexeme_entity_lexeme__getForms) returns a list of such tables.

### mw.wikibase.lexeme.entity.form:getRepresentations {#mw_wikibase_lexeme_entity_form__getRepresentations}
`entity:getRepresentations()`

Gets the representation(s) of this Form,
as a list of tables where each table has the representation text as the first element and the representation language as the second.

An example call might look like this:
```lua
form:getRepresentations() -- example: { { 'Luftballon', 'de' } }
```

## mw.wikibase.lexeme.entity.form:getRepresentation {#mw_wikibase_lexeme_entity_form__getRepresentation}
`entity:getRepresentation()`  
`entity:getRepresentation( languageCode )`

Gets the representation of this Form in the given language,
or in the content language if no language is given.
Returns the representation and its language as two strings if the Form has a representation in this language, or nil otherwise.
(Language fallbacks are not applied.)

An example call might look like this:
```lua
form:getRepresentation( 'de' ) -- example: 'Luftballon', 'de'
```

### mw.wikibase.lexeme.entity.form:getGrammaticalFeatures {#mw_wikibase_lexeme_entity_form__getGrammaticalFeatures}
`entity:getGrammaticalFeatures()`

Gets the grammatical features of this Form as a list of item IDs.

An example call might look like this:
```lua
form:getGrammaticalFeatures() -- example: { 'Q110786', 'Q131105' }
```

## mw.wikibase.lexeme.entity.form:hasGrammaticalFeature {#mw_wikibase_lexeme_entity_form__hasGrammaticalFeature}
`entity:hasGrammaticalFeature( itemId )`

Tests whether this Form has the given grammatical feature or not.

An example call might look like this:
```lua
form:hasGrammaticalFeature( 'Q110786' ) -- example: true
```

## mw.wikibase.lexeme.entity.sense {#mw_wikibase_lexeme_entity_sense}
`mw.wikibase.lexeme.entity.sense` has methods for accessing data of a loaded Sense entity.
It is typically not used directly –
`mw.wikibase.getEntity( senseId )` returns a table on which you can call the following methods,
as well as the methods from [mw.wikibase.entity][],
while [lexeme:getSenses()](#mw_wikibase_lexeme_entity_lexeme__getSenses) returns a list of such tables.

### mw.wikibase.lexeme.entity.sense:getGlosses {#mw_wikibase_lexeme_entity_sense__getGlosses}
`entity:getGlosses()`

Gets the gloss(es) of this Sense,
as a list of tables where each table has the gloss text as the first element and the gloss language as the second.

An example call might look like this:
```lua
sense:getGlosses() -- example: { { 'a rubber sack designed to be inflated with air', 'en' } }
```

## mw.wikibase.lexeme.entity.sense:getGloss {#mw_wikibase_lexeme_entity_sense__getGlosse}
`entity:getGloss()`  
`entity:getGloss( languageCode )`

Gets the gloss of this Sense in the given language,
or in the content language if no language is given.
Returns the gloss and its language as two strings if the Sense has a gloss in this language, or nil otherwise.
(Language fallbacks are not applied.)

An example call might look like this:
```lua
sense:getGloss( 'en' ) -- example: 'a rubber sack designed to be inflated with air', 'en'
```

[mw.wikibase.entity]: https://doc.wikimedia.org/Wikibase/master/php/md_docs_topics_lua.html#mw_wikibase_entity
