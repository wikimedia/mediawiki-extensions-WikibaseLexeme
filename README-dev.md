## Development setup
  - [Prerequisites](#prerequisites)
    - [MediaWiki](#mediawiki)
    - [Wikibase](#wikibase)
    - [Composer Merge](#composer-merge)
  - [Setup](#setup)
    - [1. Get WikibaseLexeme](#1-get-wikibaselexeme)
    - [2. Initialize git submodules](#2-initialize-git-submodules)
    - [3. Enable the extension in MediaWiki](#3-enable-the-extension-in-mediawiki)
    - [4. Install composer dependencies](#4-install-composer-dependencies)
    - [5. Install npm dependencies](#5-install-npm-dependencies)
  - [New Lexeme Special Page](#new-lexeme-special-page)
- [Running tests](#running-tests)
  - [PHP](#php)
  - [JavaScript](#javascript)
  - [Browser tests](#browser-tests)
  - [Other](#other)
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

The code for the Special:NewLexeme special page lives in a separate Git repository,
included as a submodule under `resources/special/new-lexeme/`.
That directory is not directly used at runtime,
but rather the build results from it are stored in this repository under `resources/special/new-lexeme-dist/`.
The following command updates the submodule to the latest `main` branch and adds a new build to Git, ready to be committed here:
```
npm run bump-special-new-lexeme
```
This command should be run from time to time (but not necessarily for every new commit in the submodule).
Note: this command must be run outside a container.

When you are working with the submodule,
and want to see the current status of your work,
you can use the following command to build and copy whatever is currently in the directory:
```
mw dev mediawiki fresh npm run snl:dev
```
Then go to Special:NewLexeme on your wiki and see the result.

Historical note: during development, the current special page was called Special:NewLexemeAlpha,
and Special:NewLexeme was a separate (older) implementation of similar functionality,
which was eventually replaced with the new implementation.
You might come across the NewLexemeAlpha name in git logs or similar.

## Running tests

### PHP
PHP tests can be found in tests/phpunit directory.

You can run tests using composer as so:
```
mw dev mediawiki exec -- composer phpunit /path/to/mw/extensions/WikibaseLexeme
```

More information about running PHP unit tests with mwcli can be found in the [mwcli interaction instructions](https://www.mediawiki.org/wiki/Cli/guide/Docker-Development-Environment/MediaWiki#PHPUnit_tests).


### JavaScript

JavaScript tests are to be found in two directories:
- `tests/qunit` (tests depending on MediaWiki)
- `tests/jasmine` (do not require MediaWiki)

JavaScript tests are run using MediaWiki test runner, except for tests of several UI widgets which are independent from MediaWiki which can by run using nodejs.

MediaWiki runner could run JavaScript tests of the extension by either opening the Special:JavaScriptTest page in the browser, or by running the following in the main directory of the MediaWiki installation (this will run all QUnit tests of the MediaWiki installation):
```
mw dev mediawiki fresh npx grunt karma
```

Jasmine tests could be run from the WikibaseLexeme main directoday using:
```
mw dev mediawiki fresh npx grunt jasmine_nodejs
```
For more information see the [Mediawiki manual for Javascript tests](https://www.mediawiki.org/wiki/Manual:JavaScript_unit_testing.

### Browser tests

Please see its [README](./tests/selenium/README.md).

### Other

Configuration files for several linters etc are also provided. The easiest way to run these along with tests, is to either run `mw dev mediawiki composer test` (for PHP code), or `mw dev mediawiki fresh grunt test` (for JavaScript part).
