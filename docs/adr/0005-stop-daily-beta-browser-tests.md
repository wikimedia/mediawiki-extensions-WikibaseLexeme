# 5) Stop Daily Beta Browser Tests {#adr_0005}

Date: 2024-01-26

## Status

accepted

## Context

Like many other MediaWiki extensions,
WikibaseLexeme used to have a daily CI job to run its browser tests against the Beta cluster.
As part of our general overhaul of the browser tests (cf. @ref adr_0004),
we had an opportunity to reevaluate whether this was useful to us.

In our experience, these tests failed relatively often,
but those failures were due to problems with the Beta cluster,
not with WikibaseLexeme,
most if not all of the time.
We were unable to recall or find any past bugfix that could be credited to the Beta browser tests.

## Decision

We will stop the daily browser test runs against Beta.

## Consequences

We will remove the corresponding CI job and the `selenium-daily` npm script.

If there are changes in MediaWiki core that break WikibaseLexeme,
and they happen at a time when we are not working on WikibaseLexeme and therefore not regularly running its CI for ordinary changes,
it’s possible that the breakage will only be noticed upon deployment to production.
(Note that this was always the case for problems caught by the PHPUnit tests,
which we never ran daily.
An option to address this, if necessary, would be a secondary CI like in Wikibase and WikibaseLexeme.)
