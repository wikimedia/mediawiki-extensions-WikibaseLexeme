This extension allows storing and modifying the structured lexicographic data in the Wikibase instance.

## Prerequisites

WikibaseLexeme requires the following extensions to be installed and configured on MediaWiki instance:
* Wikibase (the Repo component). You can find Wikibase installation instructions at https://www.mediawiki.org/wiki/Wikibase/Installation.
* ULS and CLDR (used in Senses for Language fields). You can find installation instructions of these two extensions at https://www.mediawiki.org/wiki/Extension:UniversalLanguageSelector and https://www.mediawiki.org/wiki/Extension:CLDR, respectively.

## Installation

To add WikibaseLexeme to an already installed MediaWiki Wikibase instance, please see the extension's [installation instructions](https://www.mediawiki.org/wiki/Extension:WikibaseLexeme) on MediaWiki.

## Configuration

See the [options documentation](https://doc.wikimedia.org/WikibaseLexeme/master/php/md_docs_topics_options.html).

## Development setup

### Prerequisites

#### MediaWiki

The recommended way of setting up the development environment is with the use of the [mwcli tool](https://www.mediawiki.org/wiki/Cli). To create a local MediaWiki development environment using this tool, see the [docker development environment guide](https://www.mediawiki.org/wiki/Cli/guide/Docker-Development-Environment/First-Setup) in the tool's documentation.

_**Note**: All following command examples will be using the mwcli tool, but can also be run with docker or on bare metal according to preference._

#### Wikibase

The WikibaseLexeme extension also requires Wikibase to be set up and configured in your local MediaWiki instance. To get up and running with Wikibase, follow the [installation instructions](https://www.mediawiki.org/wiki/Wikibase/Installation) on MediaWiki.

#### Composer Merge

Both Wikibase and WikibaseLexeme rely on the composer merge plugin for MediaWiki. To ensure the plugin is configured correctly, double check your `composer.local.json` file in your local MediaWiki directory against the [instructions](https://www.mediawiki.org/wiki/Composer#Using_composer-merge-plugin) on the MediaWiki website.

### Setup

#### 1. Get WikibaseLexeme

Clone this repository to the `extensions/` directory in your local MediaWiki directory:

```
$ cd <path-to-mediawiki>/extensions
$ git clone https://gerrit.wikimedia.org/r/mediawiki/extensions/WikibaseLexeme.git
```

#### 2. Initialize git submodules

To initialize and update git submodules run:

```
$ cd WikibaseLexeme
$ git submodule update --init --recursive
```

#### 3. Enable the extension in MediaWiki

Add the following line to `LocalSettings.php` at the root of you MediaWiki directory, to enable the extension:

```php
wfLoadExtension( 'WikibaseLexeme' );
```

#### 4. Install composer dependencies

To ensure all composer dependencies are installed, run composer from the root of your MediaWiki instance:

```
$ mw dev mediawiki composer install
```

#### 5. Install npm dependencies

Install all npm dependencies in order to use node development tools and scripts, using mwcli fresh:

```
$ mw dev mediawiki fresh npm install
```

### New Lexeme Special Page

The code for the Special:NewLexemeAlpha special page (soon™ to become Special:NewLexeme) lives in a separate Git repository,
included as a submodule under `resources/special/new-lexeme/`.
That directory is not directly used at runtime,
but rather the build results from it are stored in this repository under `resources/special/new-lexeme-dist/`.
The following command updates the submodule to the latest `main` branch and adds a new build to Git, ready to be committed here:
```
npm run bump-special-new-lexeme
```
This command should be run from time to time (but not necessarily for every new commit in the submodule).

When you are working with the submodule,
and want to see the current status of your work,
you can use the following command to build and copy whatever is currently in the directory:
```
npm run snl:dev
```
Then go to Special:NewLexemeAlpha on your wiki and see the result.

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

Please see its [README](./tests/selenium/README.md).

### Other

Configuration files for several linters etc are also provided. The easiest way to run these along with tests, is to either run `composer test` (for PHP code), or `grunt test` (for JavaScript part).
