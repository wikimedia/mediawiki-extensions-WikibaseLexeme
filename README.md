This extension allows storing and modifying the structured lexicographic data in the Wikibase instance.

## Installation

WikibaseLexeme requires Wikibase to be installed (the Repo component). You can find Wikibase installation instructions at https://www.mediawiki.org/wiki/Wikibase/Installation.

Note: Currently, this extension is only compatible with the current development version of Wikibase, i.e. it might not work correctly if you use older Wikibase version.

Install dependencies by running `composer install`.
Add `wfLoadExtension( 'WikibaseLexeme');` to LocalSettings.php.

## Configuration

By default WikibaseLexeme uses the namespace number 146 for storing lexeme pages, and namespace numer 147 for the related talk page. Namespaces can be customized by setting `$wgLexemeNamespace` and `$wgLexemeTalkNamespace` global variables accordingly.

When creating a new lexeme using Special:NewLexeme page, the language code of the lemma can be deducted from the item referring to the language of the lexeme. The language code would be the value of the statement on the language item that uses the specififed property. The language code property can be specified using `$wgLexemeLanguageCodePropertyId` global variable.

TODO: do we want an example for this?

## Running tests

PHP tests can be found in tests/phpunit directory.

PHP tests are run using MediaWiki test runner.
You can run tests by pointing the runner to the test directory:

php /path/to/mw/tests/phpunit.php /path/to/mw/extensions/WikibaseLexeme

Tests not requiring MediaWiki installed could be run by running `composer phpunit`.

### JavaScript

JavaScript tests are in directory `tests/qunit` (tests depending on MediaWiki), and `tests/jasmine` (do not require MediaWiki).

JavaScript tests are run using MediaWiki test runner, except for tests of several UI widgets which are independent from MediaWiki which can by run using nodejs.

MediaWiki runner could run JavaScript tests of the extension by either opening the Special:JavaScriptTest page in the browser, or by running `grunt karma` in the main directory of the MediaWiki installation (this will run all QUnit tests of the MediaWiki installation).
JavaScript tests for components independent from MediaWiki could be run using `grunt jasmine_nodejs`.

### Browser tests

Browser tests are located in tests/browser, and can be run using `bundle exec cucumber`.
You might want to add/change environment settings in `environments.yml`, and adjusting the `MEDIAWIKI_ENVIRONMENT` environment variable.

### Other

Configuration files for several linters etc are also provided. The easiest way to run these along with tests, is to either run `composer test` (for PHP code), or `grunt test` (for JavaScript part).
