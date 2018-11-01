<?php

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeAuthorizer;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\FormsStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LexemeStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\Merge\LexemeFormsMerger;
use Wikibase\Lexeme\Domain\Merge\LexemeMerger;
use Wikibase\Lexeme\Domain\Merge\LexemeRedirectCreationInteractor;
use Wikibase\Lexeme\Domain\Merge\LexemeSensesMerger;
use Wikibase\Lexeme\Domain\Merge\NoCrossReferencingLexemeStatements;
use Wikibase\Lexeme\Domain\Merge\TermListMerger;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookup;
use Wikibase\Lexeme\MediaWiki\Content\LexemeTermLanguages;
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
					new MediaWikiLexemeAuthorizer( $user, $repo->getEntityPermissionChecker() ),
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
