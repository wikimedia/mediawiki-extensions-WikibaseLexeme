This extension allows storing and modifying the structured lexicographic data in the Wikibase instance.

## Prerequisites

WikibaseLexeme requires the following extensions to be installed and configured on MediaWiki instance:
* Wikibase (the Repo component). You can find Wikibase installation instructions at https://www.mediawiki.org/wiki/Wikibase/Installation.
* ULS and CLDR (used in Senses for Language fields). You can find installation instructions of these two extensions at https://www.mediawiki.org/wiki/Extension:UniversalLanguageSelector and https://www.mediawiki.org/wiki/Extension:CLDR, respectively.

## Installation

Note: Currently, this extension is only compatible with the current development version of Wikibase, i.e. it might not work correctly if you use older Wikibase version.

Install dependencies by running `composer install`.
Add `wfLoadExtension( 'WikibaseLexeme' );` to LocalSettings.php.

## Development setup

Recommended way of setting the development environment is with the use of [Docker containers for MediaWiki development](https://github.com/addshore/mediawiki-docker-dev).

### Setting up the extension using Docker

#### Set up Mediawiki Docker Development environment

* Follow the guide available at https://www.mediawiki.org/wiki/Cli/guide/Docker-Development-Environment

* Once this is setup you can just check it all works by visiting:
[http://default.mediawiki.mwdd.localhost:8080].

* You should see a mediawiki installation all setup and running.

#### Get Wikibase Extension

* Following the setup guide from https://www.mediawiki.org/wiki/Wikibase/Installation, run:

  ```
  cd extensions
  git clone https://gerrit.wikimedia.org/r/mediawiki/extensions/Wikibase.git
  cd Wikibase
  git submodule update --init --recursive # get the dependencies using submodules
  ```

* Install the dependencies with composer:

  Add `composer.json` of Wikibase to `composer.local.json` at the root of your mediawiki folder,
  as documented in [MediaWiki's Composer documentation](https://www.mediawiki.org/wiki/Composer#Using_composer-merge-plugin)

  It should now look similar to:
  ```
  {
  "extra": {
      "merge-plugin": {
        "include": [
          "extensions/Wikibase/composer.json"
        ]
      }
    }
  }
  ```

  Using a similar command to that for installing mediawiki dependencies install the dependencies using docker and composer.

  You should run this from the root of your mediawiki installation.

  `docker run -it --rm --user $(id -u):$(id -g) -v ~/.composer:/composer -v $(pwd):/app docker.io/composer install`

  It may be that you need to run this twice. The first time to get the composer-merge-plugin and most of the libraries
  then the second time to get those which are added by the merge plugin. If update.php fails with:
  ```
  docker-compose exec "web" php /var/www/mediawiki/maintenance/update.php --wiki default --quick
  [26142080ebaf7fde12c6233c] [no req]   Error from line 35 of /var/www/mediawiki/extensions/Wikibase/lib/WikibaseLib.entitytypes.php: Class 'Wikibase\DataModel\Entity\ItemId' not found
	```

	Then try running the command again.

  Finally you may also have problems if your composer.lock file does not contain the extra dependencies pulled in by the merge-pulgin.
  In this case it may be beneficial to either remove composer.lock or run `composer update` instead of `composer install`

* Enable Extension

  Add the following lines to the `LocalSettings.php` at the root of your mediawiki folder:

  ```
  $wgEnableWikibaseRepo = true;
  $wgEnableWikibaseClient = true;
  require_once "$IP/extensions/Wikibase/repo/Wikibase.php";
  require_once "$IP/extensions/Wikibase/repo/ExampleSettings.php";
  require_once "$IP/extensions/Wikibase/client/WikibaseClient.php";
  require_once "$IP/extensions/Wikibase/client/ExampleSettings.php";
  ```

* Run the Wikibase setup scripts

  Run `update.php` in the default site from within the web docker container.
  This needs to be run from the mediawiki-docker-dev directory; the one with `docker-compose.yml`

  ```
  docker-compose exec "web" php /var/www/mediawiki/maintenance/update.php --wiki default --quick
  docker-compose exec "web" php /var/www/mediawiki/extensions/Wikibase/lib/maintenance/populateSitesTable.php --wiki default --quick
  ```

#### Get WikibaseLexeme Extension

* Clone WikibaseLexeme code

  From the extensions folder in your mediawiki run:

  `git clone https://gerrit.wikimedia.org/r/mediawiki/extensions/WikibaseLexeme`

* Add it to Config

  Add this to the `LocalSettings.php` in the root of the mediawiki repo:

  `wfLoadExtension( 'WikibaseLexeme' );`

* Install with composer

  Add `extensions/WikibaseLexeme/composer.json` to `composer.local.json` at the root of your mediawiki folder, so it looks similar to

  ```
  {
    "extra": {
      "merge-plugin": {
        "include": [
          "extensions/Wikibase/composer.json",
          "extensions/WikibaseLexeme/composer.json"
        ]
      }
    }
  }
  ```

  From the root folder of mediawiki run again

  `docker run -it --rm --user $(id -u):$(id -g) -v ~/.composer:/composer -v $(pwd):/app docker.io/composer install`

If you get `Your requirements could not be resolved to an installable set of packages` error message, delete `composer.lock` file and run the command again.

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

Please see its [README](./tests/selenium/README.md).

### Other

Configuration files for several linters etc are also provided. The easiest way to run these along with tests, is to either run `composer test` (for PHP code), or `grunt test` (for JavaScript part).
