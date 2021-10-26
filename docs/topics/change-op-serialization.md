# Change op serialization

This document describes the JSON structure used in change requests (e.g. in wbeditentity API requests) regarding elements specific to this extension.

See docs/topics/changeop-serializations.md in Wikibase Git repository for general overview of syntax of change request data.

## Lexeme entity elements

### Language

* A language is defined by adding a "language" key to the change request JSON array. The value should be a string containing the ID of an existing item. That item should be a about a language (for example French or Chinese), but we don't enforce that, and constraint reports should take care of such violations.
* Language can't be null or empty.

Example of a change request setting language to "Q666" can be seen below (only relevant parts of the request included in the example).

```json
{
	...
	"language": "Q666"
	...
}
```

### Lemmas

Structure used to define lemma data uses the syntax generally used in Wikibase for Term lists, ie. it is similar to what "core" Wikibase uses for labels of Items and Properties.

* Lemma or lemmas are defined by adding a "lemmas" key to change request JSON array. Value should be an associative array indexed with language codes. Each element of this array specifies a change to the lemma in the relevant language or variant:
  * In order to set a lemma for a given language code, "language" and "value" elements must be provided.
  * In order to remove a lemma for a given language code, "language" and "remove" elements must be provided. Content of the "remove" element is not relevant, typically an empty string is used.
  * Lemma for a given language could be also removed by providing an empty string as a "value".
* It is also technically possible to omit language-code keys in "lemmas" array, i.e. use a list instead of a map.

Example of a change request setting lemma in language "en" to "colour" can be seen below (only relevant parts of the request included in the example).

```json
{
    ...
    "lemmas": {
        "en": { "language": "en", "value": "colour" },
	}
	...
}
```

### Lexical category
* The lexical category is defined by adding a "lexicalCategory" key to the change request JSON array. The value should be a string containing the ID of an existing item. That item should be about a lexical category (for example verb or noun), but we don't enforce that, and constraint reports should take care of such violations.
* Lexical category can't be null or empty.

Example of a change request setting lexical category to "Q42" can be seen below (only relevant parts of the request included in the example).

```json
{
	...
	"lexicalCategory": "Q42"
	...
}
```
