<?php

namespace Wikibase\Lexeme;

use MediaWiki\MediaWikiServices;
use RequestContext;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeAuthorizer;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRepository;
use Wikibase\Lexeme\Domain\Authorization\LexemeAuthorizer;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\FormsStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LexemeStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\Merge\LexemeFormsMerger;
use Wikibase\Lexeme\Domain\Merge\LexemeMerger;
use Wikibase\Lexeme\Domain\Merge\LexemeRedirectCreationInteractor;
use Wikibase\Lexeme\Domain\Merge\LexemeSensesMerger;
use Wikibase\Lexeme\Domain\Merge\NoCrossReferencingLexemeStatements;
use Wikibase\Lexeme\Domain\Storage\LexemeRepository;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookup;
use Wikibase\Lexeme\MediaWiki\Content\LexemeTermLanguages;
use Wikibase\Repo\EditEntity\MediawikiEditFilterHookRunner;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeServices {

	private static $globalInstance;

	/**
	 * @param bool $botEditRequested Whether the user has requested that edits be marked as bot edits.
	 * @return WikibaseLexemeServices
	 */
	public static function createGlobalInstance( $botEditRequested ): self {
		self::$globalInstance = new self(
			RequestContext::getMain(),
			$botEditRequested
		);

		return self::$globalInstance;
	}

	public static function globalInstance(): self {
		if ( self::$globalInstance === null ) {
			throw new \RuntimeException( 'Cannot get global instance without first initializing it' );
		}

		return self::$globalInstance;
	}

	public static function newTestInstance(): self {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new \Exception(
				'Cannot get newTestInstance during regular operation.'
			);
		}
		return new self( RequestContext::getMain(), false );
	}

	private $container = [];

	private $mediaWikiContext;
	private $botEditRequested = false;

	private function __construct( RequestContext $mediaWikiContext, /* bool */$botEditRequested ) {
		$this->mediaWikiContext = $mediaWikiContext;
		$this->botEditRequested = $botEditRequested;
	}

	/**
	 * @return mixed
	 */
	private function getSharedService( /* string */ $serviceName, callable $constructionFunction ) {
		if ( !array_key_exists( $serviceName, $this->container ) ) {
			$this->container[$serviceName] = $constructionFunction();
		}

		return $this->container[$serviceName];
	}

	public function newMergeLexemesInteractor(): MergeLexemesInteractor {
		return new MergeLexemesInteractor(
			$this->newLexemeMerger(),
			$this->getLexemeAuthorizer(),
			$this->getWikibaseRepo()->getSummaryFormatter(),
			$this->newLexemeRedirectCreationInteractor(),
			$this->getWikibaseRepo()->getEntityTitleLookup(),
			MediaWikiServices::getInstance()->getWatchedItemStore(),
			$this->getLexemeRepository()
		);
	}

	private function getLexemeRepository(): LexemeRepository {
		return $this->getSharedService(
			LexemeRepository::class,
			function() {
				return new MediaWikiLexemeRepository(
					RequestContext::getMain()->getUser(),
					$this->botEditRequested,
					$this->getWikibaseRepo()->getEntityStore(),
					$this->getWikibaseRepo()->getEntityRevisionLookup()
				);
			}
		);
	}

	private function newLexemeMerger(): LexemeMerger {
		$statementsMerger = $this->getWikibaseRepo()
			->getChangeOpFactoryProvider()
			->getMergeFactory()
			->getStatementsMerger();

		return new LexemeMerger(
			$statementsMerger,
			new LexemeFormsMerger(
				$statementsMerger,
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

	private function getLexemeAuthorizer(): LexemeAuthorizer {
		return $this->getSharedService(
			LexemeAuthorizer::class,
			function() {
				return new MediaWikiLexemeAuthorizer(
					RequestContext::getMain()->getUser(),
					$this->getWikibaseRepo()->getEntityPermissionChecker()
				);
			}
		);
	}

	private function newLexemeRedirectCreationInteractor(): LexemeRedirectCreationInteractor {
		return new LexemeRedirectCreationInteractor(
			$this->getWikibaseRepo()->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			$this->getWikibaseRepo()->getEntityStore(),
			$this->getWikibaseRepo()->getEntityPermissionChecker(),
			$this->getWikibaseRepo()->getSummaryFormatter(),
			RequestContext::getMain()->getUser(),
			new MediawikiEditFilterHookRunner(
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

	public static function getTermLanguages(): LexemeTermLanguages {
		return MediaWikiServices::getInstance()->getService( 'WikibaseLexemeTermLanguages' );
	}

	public static function getLanguageNameLookup(): LexemeLanguageNameLookup {
		return MediaWikiServices::getInstance()->getService( 'WikibaseLexemeLanguageNameLookup' );
	}

	public static function getEditFormChangeOpDeserializer(): EditFormChangeOpDeserializer {
		return MediaWikiServices::getInstance()->getService(
			'WikibaseLexemeEditFormChangeOpDeserializer'
		);
	}

}
