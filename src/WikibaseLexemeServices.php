<?php

namespace Wikibase\Lexeme;

use MediaWiki\MediaWikiServices;
use RequestContext;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeAuthorizer;
use Wikibase\Lexeme\Domain\Authorization\LexemeAuthorizer;
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

	private function __construct() {
	}

	public function newMergeLexemesInteractor(): MergeLexemesInteractor {
		return new MergeLexemesInteractor(
			$this->newLexemeMerger(),
			$this->getWikibaseRepo()->getEntityRevisionLookup(),
			$this->getWikibaseRepo()->getEntityStore(),
			$this->newLexemeAuthorizer(),
			$this->getWikibaseRepo()->getSummaryFormatter(),
			RequestContext::getMain()->getUser(),
			$this->newLexemeRedirectCreationInteractor(),
			$this->getWikibaseRepo()->getEntityTitleLookup(),
			MediaWikiServices::getInstance()->getWatchedItemStore()
		);
	}

	private function newLexemeMerger(): LexemeMerger {
		$statementsMerger = $this->getWikibaseRepo()
			->getChangeOpFactoryProvider()
			->getMergeFactory()
			->getStatementsMerger();

		return new LexemeMerger(
			new TermListMerger(),
			$statementsMerger,
			new LexemeFormsMerger(
				$statementsMerger,
				new TermListMerger(),
				new GuidGenerator()
			),
			new LexemeSensesMerger(),
			$this->newNoCrossReferencingLexemeStatements()
		);
	}

	private function newNoCrossReferencingLexemeStatements(): NoCrossReferencingLexemeStatements {
		$baseExtractor = new StatementEntityReferenceExtractor(
			$this->getWikibaseRepo()->getLocalItemUriParser()
		);

		return new NoCrossReferencingLexemeStatements(
			new LexemeStatementEntityReferenceExtractor(
				$baseExtractor,
				new FormsStatementEntityReferenceExtractor( $baseExtractor ),
				new SensesStatementEntityReferenceExtractor( $baseExtractor )
			)
		);
	}

	// TODO: shared service? (depends on user stability)
	private function newLexemeAuthorizer(): LexemeAuthorizer {
		return new MediaWikiLexemeAuthorizer(
			RequestContext::getMain()->getUser(),
			$this->getWikibaseRepo()->getEntityPermissionChecker()
		);
	}

	private function newLexemeRedirectCreationInteractor(): LexemeRedirectCreationInteractor {
		return new LexemeRedirectCreationInteractor(
			$this->getWikibaseRepo()->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			$this->getWikibaseRepo()->getEntityStore(),
			$this->getWikibaseRepo()->getEntityPermissionChecker(),
			$this->getWikibaseRepo()->getSummaryFormatter(),
			RequestContext::getMain()->getUser(),
			new EditFilterHookRunner(
				$this->getWikibaseRepo()->getEntityNamespaceLookup(),
				$this->getWikibaseRepo()->getEntityTitleLookup(),
				$this->getWikibaseRepo()->getEntityContentFactory(),
				RequestContext::getMain()
			),
			$this->getWikibaseRepo()->getStore()->getEntityRedirectLookup(),
			$this->getWikibaseRepo()->getEntityTitleLookup()
		);
	}

	private function getWikibaseRepo(): WikibaseRepo {
		return WikibaseRepo::getDefaultInstance();
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
