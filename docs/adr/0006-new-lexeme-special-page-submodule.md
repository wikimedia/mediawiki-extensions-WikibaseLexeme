# 6) Integration of the New Lexeme Special Page component (submodule) {#adr_0006}

Date: 2026-04-21

## Status

proposed

## Decision

The component containing the Special:NewLexeme logic will be transferred to a dedicated repository on Wikimedia Gerrit. The component will continue to be integrated in the Wikibase Lexeme repository as a git submodule.

## Context

The Special:NewLexeme special page has been developed in the separate repository and has been included in Wikibase Lexeme repository as a git submodule. The special page code has not been directly used in Wikibase Lexeme UI but the special page TypeScript logic, once included in the Wikibase Lexeme repository, has been built to a distribution format and included in the Wikibase Lexeme UI logic. The commited built version of the special page is only updated when the new version is supposed to be delivered to Wikidata, making the development of the component independent from the delivery of the new version of the special page to Wikidata.

The special page has been developed on Github. The Github-specific infrastructure has included CI using Github Actions, and integration with the Netlify service that automatically provided the ability to preview and test any code change (pull request).

As Wikimedia Foundation does not deploy code hosted on third-party infrastructure, in order to allow deployments of Wikibase Lexeme code including the submodule, it is integrated through a WMF-hosted Phabricator mirror. ([T301273][phab-instead-github])

In 2025 Wikimedia Phabricator started experiencing availability issues related to crawling. Availability of Phabricator-hosted submodules has been also affected, commonly leading to failures when cloning Wikibase Lexeme repository. ([T409519][phab-cloning-timeouts])

## Options considered

### Option 1: (selected) Move Special Page repository to Wikimedia Gerrit

#### Consequences

* Selected because the Special page component remains integrated into Wikibase Lexeme as a git submodule, resulting in no significant changes needed in Wikibase Lexeme.
* Selected despite it requires re-creating the test and other development (e.g. Dependabot) workflows that have been in use in Github repository
* Selected despite it requires a change in the development workflow for the moved component per the different paradigm in Gerrit
* Selected despite the ability to automatically get a preview of the incoming code changes through integration with Netlify service might not be possible, or require some non-trivial adjustments.
* Selected because it reduces amount of platforms/system on which the source code of the Wikibase components are hosted.

### Option 2: Move Special Page repository to Wikimedia Gitlab

#### Consequences

* Special page component remains integrated into Wikibase Lexeme as a git submodule. No significant changes needed in Wikibase Lexeme.
* Requires re-creating the test and other development (e.g. Dependabot) workflows that have been in use in Github repository
* It might be possible to include the development history (e.g. pull request history) from Github through Gitlab's import functionality
* Ability to automatically get a preview of the incoming code changes through integration with Netlify service might not be possible, or require some non-trivial adjustments.

### Option 3: Merge the Special Page component to Wikibase Lexeme repository

#### Consequences

* Requires a change to the development workflow as every change to the special page component would likely also require commiting the built special page logic.
* Requires re-creating the test and other development (e.g. Dependabot) workflows that have been in use in Github repository
* Cloning Wikibase Lexeme repository might become slightly faster due to fewer separate cloning operations involved -- However the size of files in the repository and their history and metadata will increase
* History of changes of the special page component will be preserved

## Advice

* Lucas W advises moving the Special Page repository to either Wikimedia Gitlab or Wikimedia Gerrit. Moving to Wikimedia Gitlab has an advantage of the Gitlab workflow being rather similar to the current Github workflow. On the other hand hosting this component on Wikimedia Gerrit would simplify the overall set up of Wikibase and Wikibase Lexeme, with most, if not all, components hosted and deployed to Wikidata from Wikimedia Gerrit.
* Tom A advises to merge the Special Page component to Wikibase Lexeme repository as it would reduce the number of repositories maintained, and it would make the Wikibase Lexeme set up simpler and easier to work with no submodules involved. Additionally, it would enable the ability to preview incoming changes using Wikimedia Patch Demo service, and also remove the need for dependency on the Netlify service. Tom is of opinion that the tradeoff of needing to check in built assets to repository would not immediately be a significant issue given the component is currently and in foreseeable future not very actively developed.

[phab-instead-github]: https://phabricator.wikimedia.org/T301273
[phab-cloning-timeouts]: https://phabricator.wikimedia.org/T409519
