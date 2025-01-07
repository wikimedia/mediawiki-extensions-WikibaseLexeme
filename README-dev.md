# Development setup

## Prerequisites

### MediaWiki

The recommended way of setting up the development environment is with the use of the [mwcli tool](https://www.mediawiki.org/wiki/Cli). To create a local MediaWiki development environment using this tool, see the [docker development environment guide](https://www.mediawiki.org/wiki/Cli/guide/Docker-Development-Environment/First-Setup) in the tool's documentation.

_**Note**: All following command examples will be using the mwcli tool, but can also be run with docker or on bare metal according to preference._

### Wikibase

The WikibaseLexeme extension also requires Wikibase to be set up and configured in your local MediaWiki instance. To get up and running with Wikibase, follow the [installation instructions](https://www.mediawiki.org/wiki/Wikibase/Installation) on MediaWiki.

### Composer Merge

Both Wikibase and WikibaseLexeme rely on the composer merge plugin for MediaWiki. To ensure the plugin is configured correctly, double check your `composer.local.json` file in your local MediaWiki directory against the [instructions](https://www.mediawiki.org/wiki/Composer#Using_composer-merge-plugin) on the MediaWiki website.

## Setup

### 1. Get WikibaseLexeme

Clone this repository to the `extensions/` directory in your local MediaWiki directory:

```
$ cd <path-to-mediawiki>/extensions
$ git clone https://gerrit.wikimedia.org/r/mediawiki/extensions/WikibaseLexeme.git
```

### 2. Initialize git submodules

To initialize and update git submodules run:

```
$ cd WikibaseLexeme
$ git submodule update --init --recursive
```

### 3. Enable the extension in MediaWiki

Add the following line to `LocalSettings.php` at the root of you MediaWiki directory, to enable the extension:

```php
wfLoadExtension( 'WikibaseLexeme' );
```

### 4. Install composer dependencies

To ensure all composer dependencies are installed, run composer from the root of your MediaWiki instance:

```
$ mw dev mediawiki composer install
```

### 5. Install npm dependencies

Install all npm dependencies in order to use node development tools and scripts, using mwcli fresh:

```
$ mw dev mediawiki fresh npm install
```

## New Lexeme Special Page

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

If you try to update the submodule right after merging a commit on GitHub, you might see the following error message in CI:
```
error: Server does not allow request for unadvertised object <commit hash>
Fetched in submodule path 'resources/special/new-lexeme', but it did not contain <commit hash>. Direct fetching of that commit failed.
```
In that case, just try again a bit later.

The background is, that due to operational reasons, CI will not pull the code from a GitHub remote.
Therefore [a mirror in Phabricator's Diffusion](https://phabricator.wikimedia.org/diffusion/NLSP/repository/main/) is used instead (see [T301273](https://phabricator.wikimedia.org/T301273)).
Its update frequency is dynamic and can be seen on its [status page](https://phabricator.wikimedia.org/diffusion/NLSP/manage/).

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

We use Cypress for browser testing. See the [Cypress README](./cypress/README.md) for details.

### Adding language code support for lexemes

To add a new language code for lexemes please refer to the [detailed manual](https://www.mediawiki.org/wiki/Manual:Adding_and_removing_languages#Adding_a_language_code_for_Lexemes).

### Other

Configuration files for several linters etc are also provided. The easiest way to run these along with tests, is to either run `mw dev mediawiki composer test` (for PHP code), or `mw dev mediawiki fresh grunt test` (for JavaScript part).

## Chore: Dependency Updates

As of November 2023, we have to update dependencies manually because LibraryUpgrader (LibUp) is broken: [T345930](https://phabricator.wikimedia.org/T345930)

### JS (npm) dependencies

You can see which dependencies have new releases by first making sure your local dependencies are up-to-date by executing `npm ci` and then running `npm outdated`.
The following dependencies are special cases that should potentially be ignored:

- Vue and Vuex:
  in production, we use the versions shipped by MediaWiki core,
  so we should use the same versions for testing.
  The current versions shipped by MediaWiki core are listed in [foreign-resources.yaml](https://gerrit.wikimedia.org/g/mediawiki/core/+/master/resources/lib/foreign-resources.yaml).
- [grunt-eslint](https://github.com/sindresorhus/grunt-eslint) no longer supports "flat" eslint config files (i.e. `.eslintrc.json`) since version 25.0.0 because of changes since eslint 9 (see issue [#176](https://github.com/sindresorhus/grunt-eslint/issues/176)). See [T364065](https://phabricator.wikimedia.org/T364065) for progress with our eslint 9 migration.
- Any dependencies that are not compatible with Node 20, which we use in CI.
  <!-- Ticket TBC [see T343827 for Node 20] tracks the upgrade to Node 22. -->

All other dependencies should generally be updated to the latest version.
If you discover that a dependency should not be updated for some reason, please add it to the above list.
If a dependency can only be updated with substantial manual work,
you can create a new task for it and skip it in the context of the current chore.

The recommended way to update dependencies is to collect related dependency updates into grouped commits;
this keeps the number of commits to review manageable (compared to having one commit for every update),
while keeping the scope of each commit limited and increasing reviewability and debuggability (compared to combining all updates in a single commit).
For example, this can be one commit for each of:

- all ESLint-related dependency updates
- all Stylelint-related dependency updates
- `npm update` for all other dependency updates

Make sure that all checks still pass for every commit.

### PHP (composer) dependencies

Make sure your local dependencies are up-to-date by running `composer update`,
then run `composer outdated --direct` to check that direct dependencies are up to date.
Most of the time, there are few enough libraries to upgrade that no grouping is necessary.
The following dependencies are special cases that should potentially be ignored:

- giorgiosironi/eris: Needs to stay on 0.14 until we drop support for PHP < 8.1 (1.0.0 requires PHP >= 8.1).
