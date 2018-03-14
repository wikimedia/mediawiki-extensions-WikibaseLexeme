All command examples are assuming to be run from the main directory of MediaWiki core.

## Running with all MediaWiki browser tests

Runs all MediaWiki core browser tests, and tests of all extensions (including this one):

    npm run selenium

## Running selected tests

Start chromedriver:

    chromedriver --url-base=wd/hub --port=4444

Run tests from the specification file COOL-TESTS.js

    node_modules/.bin/wdio tests/selenium/wdio.conf.js --baseUrl WIKI_URL --spec /path/to/extensions/tests/selenium/specs/COOL-TESTS.js

Unless you're running tests against a wiki running at 127.0.0.1:8080, when `baseUrl` can be omitted:

    node_modules/.bin/wdio tests/selenium/wdio.conf.js --spec /path/to/extensions/tests/selenium/specs/COOL-TESTS.js
