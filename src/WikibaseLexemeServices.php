<?php

namespace Wikibase\Lexeme;

use MediaWiki\MediaWikiServices;
use RequestContext;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeAuthorizer;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRedirector;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRepository;
use Wikibase\Lexeme\Domain\Authorization\LexemeAuthorizer;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\FormsStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LexemeStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\LexemeRedirector;
use Wikibase\Lexeme\Domain\Merge\LexemeFormsMerger;
use Wikibase\Lexeme\Domain\Merge\LexemeMerger;
use Wikibase\Lexeme\Domain\Merge\LexemeSensesMerger;
use Wikibase\Lexeme\Domain\Merge\NoCrossReferencingLexemeStatements;
use Wikibase\Lexeme\Domain\Storage\LexemeRepository;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookup;
use Wikibase\Lexeme\MediaWiki\Content\LexemeTermLanguages;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;

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
		$mwServices = MediaWikiServices::getInstance();
		return new MergeLexemesInteractor(
			$this->newLexemeMerger(),
			$this->getLexemeAuthorizer(),
			WikibaseRepo::getSummaryFormatter( $mwServices ),
			$this->newLexemeRedirector(),
			WikibaseRepo::getEntityTitleStoreLookup( $mwServices ),
			$mwServices->getWatchedItemStore(),
			$this->getLexemeRepository()
		);
	}

	private function getLexemeRepository(): LexemeRepository {
		return $this->getSharedService(
			LexemeRepository::class,
			function () {
				return new MediaWikiLexemeRepository(
					RequestContext::getMain()->getUser(),
					$this->botEditRequested,
					WikibaseRepo::getEntityStore(),
					WikibaseRepo::getEntityRevisionLookup(),
					MediaWikiServices::getInstance()->getPermissionManager()
				);
			}
		);
	}

	private function newLexemeMerger(): LexemeMerger {
		$statementsMerger = WikibaseRepo::getChangeOpFactoryProvider()
			->getMergeFactory()
			->getStatementsMerger();

		$guidGenerator = new GuidGenerator();

		return new LexemeMerger(
			$statementsMerger,
			new LexemeFormsMerger(
				$statementsMerger,
				$guidGenerator
			),
			new LexemeSensesMerger(
				$guidGenerator
			),
			$this->newNoCrossReferencingLexemeStatements()
		);
	}

	private function newNoCrossReferencingLexemeStatements(): NoCrossReferencingLexemeStatements {
		$baseExtractor = new StatementEntityReferenceExtractor(
			WikibaseRepo::getItemUrlParser()
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
			static function () {
				return new MediaWikiLexemeAuthorizer(
					RequestContext::getMain()->getUser(),
					WikibaseRepo::getEntityPermissionChecker()
				);
			}
		);
	}

	private function newLexemeRedirector(): LexemeRedirector {
		return new MediaWikiLexemeRedirector(
			WikibaseRepo::getStore()->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			WikibaseRepo::getEntityStore(),
			WikibaseRepo::getEntityPermissionChecker(),
			WikibaseRepo::getSummaryFormatter(),
			RequestContext::getMain(),
			WikibaseRepo::getEditFilterHookRunner(),
			WikibaseRepo::getStore()->getEntityRedirectLookup(),
			WikibaseRepo::getEntityTitleStoreLookup(),
			$this->botEditRequested
		);
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
