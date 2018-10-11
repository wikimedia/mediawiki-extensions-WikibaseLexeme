<?php

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookup;
use Wikibase\Lexeme\MediaWiki\Content\LexemeTermLanguages;
use Wikibase\Lexeme\EntityReferenceExtractors\FormsStatementEntityReferenceExtractor;
use Wikibase\Lexeme\EntityReferenceExtractors\LexemeStatementEntityReferenceExtractor;
use Wikibase\Lexeme\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Lexeme\Merge\LexemeFormsMerger;
use Wikibase\Lexeme\Merge\LexemeMerger;
use Wikibase\Lexeme\Merge\LexemeRedirectCreationInteractor;
use Wikibase\Lexeme\Merge\LexemeSensesMerger;
use Wikibase\Lexeme\Merge\TermListMerger;
use Wikibase\Lexeme\Validators\NoCrossReferencingLexemeStatements;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
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

				$baseExtractor = new StatementEntityReferenceExtractor( $repo->getLocalItemUriParser() );
				$noCrossReferencingStatementsValidator = new NoCrossReferencingLexemeStatements(
					new LexemeStatementEntityReferenceExtractor(
						$baseExtractor,
						new FormsStatementEntityReferenceExtractor( $baseExtractor ),
						new SensesStatementEntityReferenceExtractor( $baseExtractor )
					)
				);

				return new MergeLexemesInteractor(
					new LexemeMerger(
						new TermListMerger(),
						$statementsMerger,
						new LexemeFormsMerger(
							$statementsMerger,
							new TermListMerger(),
							new GuidGenerator()
						),
						new LexemeSensesMerger(),
						$noCrossReferencingStatementsValidator
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
			},
		'WikibaseLexemeEditFormChangeOpDeserializer' => function (
			MediaWikiServices $mediaWikiServices
		) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			return new EditFormChangeOpDeserializer(
				new RepresentationsChangeOpDeserializer(
					new TermDeserializer(),
					new LexemeTermSerializationValidator(
						new LexemeTermLanguageValidator( WikibaseLexemeServices::getTermLanguages() )
					)
				),
				new ItemIdListDeserializer( new ItemIdParser() ),
				new ClaimsChangeOpDeserializer(
					$wikibaseRepo->getExternalFormatStatementDeserializer(),
					$wikibaseRepo->getChangeOpFactoryProvider()->getStatementChangeOpFactory()
				)
			);
		},
	];
} );
