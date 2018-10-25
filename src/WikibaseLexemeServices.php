<?php

namespace Wikibase\Lexeme;

use MediaWiki\MediaWikiServices;
use RequestContext;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
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
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookup;
use Wikibase\Lexeme\MediaWiki\Content\LexemeTermLanguages;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeServices {

	public static function globalInstance() {
		static $instance = null;

		if ( $instance === null ) {
			$instance = new self();
		}

		return $instance;
	}

	public function newMergeLexemesInteractor(): MergeLexemesInteractor {
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
			MediaWikiServices::getInstance()->getWatchedItemStore()
		);
	}

	public static function getTermLanguages() : LexemeTermLanguages {
		return MediaWikiServices::getInstance()->getService( 'WikibaseLexemeTermLanguages' );
	}

	public static function getLanguageNameLookup() : LexemeLanguageNameLookup {
		return MediaWikiServices::getInstance()->getService( 'WikibaseLexemeLanguageNameLookup' );
	}

	public static function getLexemeMergeInteractor() : MergeLexemesInteractor {
		return self::globalInstance()->newMergeLexemesInteractor();
	}

	public static function getEditFormChangeOpDeserializer() : EditFormChangeOpDeserializer {
		return MediaWikiServices::getInstance()->getService(
			'WikibaseLexemeEditFormChangeOpDeserializer'
		);
	}

}
