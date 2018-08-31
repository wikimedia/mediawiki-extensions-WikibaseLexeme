<?php

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\Content\LexemeLanguageNameLookup;
use Wikibase\Lexeme\Content\LexemeTermLanguages;
use Wikibase\Lexeme\Merge\LexemeFormsMerger;
use Wikibase\Lexeme\Merge\LexemeMergeInteractor;
use Wikibase\Lexeme\Merge\LexemeMerger;
use Wikibase\Lexeme\Merge\LexemeRedirectCreationInteractor;
use Wikibase\Lexeme\Merge\TermListMerger;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store;

// TODO Replace by framework-agnostic DI container.
// Pimple e.g. is well known in the free world and yet part of mediawiki-vendor
// Challenge: Dedicated API endpoints (e.g. AddForm) need to have it passed w/o singletons/globals
return call_user_func( function() {
	// TODO Problem when removing a code after such an item exists in DB
	$additionalLanguages = [ 'mis' ];

	return [
		'WikibaseLexemeTermLanguages' =>
			function( MediaWikiServices $mediawikiServices ) use ( $additionalLanguages ) {
				return new LexemeTermLanguages(
					$additionalLanguages
				);
			},
		'WikibaseLexemeLanguageNameLookup' =>
			function( MediaWikiServices $mediawikiServices ) use ( $additionalLanguages ) {
				return new LexemeLanguageNameLookup(
					RequestContext::getMain(),
					$additionalLanguages,
					WikibaseRepo::getDefaultInstance()->getLanguageNameLookup()
				);
			},
		'WikibaseLexemeMergeInteractor' =>
			function( MediaWikiServices $mediaWikiServices ) {
				$repo = WikibaseRepo::getDefaultInstance();

				$requestContext = RequestContext::getMain();
				$user = $requestContext->getUser();
				$statementsMerger = $repo
					->getChangeOpFactoryProvider()
					->getMergeFactory()
					->getStatementsMerger();
				return new LexemeMergeInteractor(
					new LexemeMerger(
						new TermListMerger(),
						$statementsMerger,
						new LexemeFormsMerger(
							$statementsMerger,
							new TermListMerger(),
							new GuidGenerator()
						)
					),
					$repo->getEntityRevisionLookup(),
					$repo->getEntityStore(),
					$repo->getEntityPermissionChecker(),
					$repo->getSummaryFormatter(),
					$user,
					new LexemeRedirectCreationInteractor(
						$repo->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
						$repo->getEntityStore(),
						$repo->getEntityPermissionChecker(),
						$repo->getSummaryFormatter(),
						$user,
						new EditFilterHookRunner(
							$repo->getEntityNamespaceLookup(),
							$repo->getEntityTitleLookup(),
							$repo->getEntityContentFactory(),
							$requestContext
						),
						$repo->getStore()->getEntityRedirectLookup(),
						$repo->getEntityTitleLookup()
					),
					$repo->getEntityTitleLookup(),
					$mediaWikiServices->getWatchedItemStore()
				);
			}
	];
} );
