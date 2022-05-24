# Options

This document describes the configuration of WikibaseLexeme.

As usual, the extension is configured in MediaWiki's `LocalSettings.php` file.
Like most other extensions, but unlike Wikibase (see [Wikibase: Options][]),
settings are configured directly as global variables, starting with `$wg`.

## Repo/Client options

WikibaseLexeme combines both “repo” and “client” functionality in a single extension.
The “repo” part is used together with the WikibaseRepo extension,
adding Lexemes as a new entity type that can be stored and used on the wiki.
The “client” part is used together with the WikibaseClient extension,
allowing access to Lexeme data stored on a repo wiki.
The “client” functionality is always enabled.

### $wgLexemeEnableRepo

This option can be used to control whether the “repo” functionality is enabled or not.

By default, it is enabled if and only if WikibaseRepo is enabled;
by setting this variable to `false`, you can prevent the “repo” functionality from being enabled
on a wiki that is a repository for other entity types,
but should only have “client” functionality enabled for lexemes.

If WikibaseRepo is not enabled, “repo” functionality is never enabled,
and this cannot be overridden using this option.

DEFAULT: `true`

## Repo options

These options apply only to “repo” functionality.

### $wgLexemeNamespace

The number of the namespace in which WikibaseLexeme stores Lexeme entities.
A corresponding talk namespace is automatically registered as well.
Set to `false` to disable registering namespaces automatically.

DEFAULT: `146` (therefore the default talk namespace is `147`)

### $wgLexemeLanguageCodePropertyId

The Property ID of a Property that stores the language code of a language Item.
This is used on the Special:NewLexeme page to deduce/infer the language code of the lemma.
If the user selects a language Item with no statement for this Property,
or if this option is not configured,
the user will have to enter a language code (spelling variant) manually.

The Property must have the data value type “string”;
usual data types are “string” or “external identifier”.

DEFAULT: `null`

### $wgLexemeLexicalCategoryItemIds

A list of Item IDs of Items that should be suggested as lexical categories.
This is used on the Special:NewLexeme page to present the user with more useful search results.
Other Items can still be selected and used as the lexical category of a Lexeme.

DEFAULT: `[]`

## Client options

These options only apply to “client” functionality.

### $wgLexemeEnableDataTransclusion

Whether to enable access to lexicographical data via Lua.
When this is enabled, the [WikibaseLexeme Lua interface][] becomes available.
This option corresponds to the [WikibaseClient allowDataTransclusion setting][].

DEFAULT: `false`

[Wikibase: Options]: https://doc.wikimedia.org/Wikibase/master/php/md_docs_topics_options.html
[WikibaseLexeme Lua interface]: @ref md_docs_topics_lua
[WikibaseClient allowDataTransclusion setting]: https://doc.wikimedia.org/Wikibase/master/php/md_docs_topics_options.html#client_allowDataTransclusion
