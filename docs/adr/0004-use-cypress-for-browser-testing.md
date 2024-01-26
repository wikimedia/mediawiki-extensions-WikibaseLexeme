# 4) Use Cypress for Browser Testing {#adr_0004}

Date: 2024-01-26

## Status

accepted

## Context

WikibaseLexeme browser tests used to be written using WebdriverIO (wdio) / Selenium in sync mode.
This is no longer supported since Node 16 and WebdriverIO 8.
As a result, we have already had to [stop running the browser tests in CI](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/WikibaseLexeme/+/944969),
and need to decide how to move forward with our browser tests.

## Considered Actions

We identified the following options:

1. Rewrite the tests using async mode of WebdriverIO.
2. Rewrite the tests using Cypress and drop WebdriverIO.

See also [EntitySchema ADR 0002](https://gerrit.wikimedia.org/g/mediawiki/extensions/EntitySchema/+/master/docs/adr/0002-use-cypress-for-browser-testing.md),
where we were faced with the same decision,
for more details on each option.

(The theoretically possible option of not having any browser tests was not seriously considered.)

## Decision

We will go with Cypress.

## Consequences

We will set up Cypress testing,
then rewrite the existing tests in Cypress and drop their WebdriverIO code.
