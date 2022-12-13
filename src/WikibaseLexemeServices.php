<?php

namespace Wikibase\Lexeme;

use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;
use RequestContext;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\DataAccess\Store\LemmaLookup;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeAuthorizer;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRedirectorFactory;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRepositoryFactory;
use Wikibase\Lexeme\Domain\Authorization\LexemeAuthorizer;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\FormsStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LexemeStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\Merge\LexemeFormsMerger;
use Wikibase\Lexeme\Domain\Merge\LexemeMerger;
use Wikibase\Lexeme\Domain\Merge\LexemeSensesMerger;
use Wikibase\Lexeme\Domain\Merge\NoCrossReferencingLexemeStatements;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookupFactory;
use Wikibase\Lexeme\MediaWiki\Content\LexemeTermLanguages;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lib\Store\ItemOrderProvider;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeServices {

	public static function newInstance(): self {
		return new self();
	}

	private $container = [];

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
			$this->newLexemeRedirectorFactory(),
			WikibaseRepo::getEntityTitleStoreLookup( $mwServices ),
			$mwServices->getWatchedItemStore(),
			$this->getLexemeRepositoryFactory()
		);
	}

	private function getLexemeRepositoryFactory(): MediaWikiLexemeRepositoryFactory {
		return $this->getSharedService(
			MediaWikiLexemeRepositoryFactory::class,
			static function () {
				return new MediaWikiLexemeRepositoryFactory(
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

	private function newLexemeRedirectorFactory(): MediaWikiLexemeRedirectorFactory {
		return new MediaWikiLexemeRedirectorFactory(
			WikibaseRepo::getStore()->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			WikibaseRepo::getEntityStore(),
			WikibaseRepo::getEntityPermissionChecker(),
			WikibaseRepo::getSummaryFormatter(),
			WikibaseRepo::getEditFilterHookRunner(),
			WikibaseRepo::getStore()->getEntityRedirectLookup(),
			WikibaseRepo::getEntityTitleStoreLookup()
		);
	}

	public static function getTermLanguages(): LexemeTermLanguages {
		return MediaWikiServices::getInstance()->getService( 'WikibaseLexemeTermLanguages' );
	}

	public static function getLanguageNameLookupFactory(): LexemeLanguageNameLookupFactory {
		return MediaWikiServices::getInstance()
			->getService( 'WikibaseLexemeLanguageNameLookupFactory' );
	}

	public static function getLemmaLookup(
		ContainerInterface $services = null
	): LemmaLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseLexemeLemmaLookup' );
	}

	public static function getLemmaTermValidator(
		ContainerInterface $services = null
	): LemmaTermValidator {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseLexemeLemmaTermValidator' );
	}

	public static function getEditFormChangeOpDeserializer(): EditFormChangeOpDeserializer {
		return MediaWikiServices::getInstance()->getService(
			'WikibaseLexemeEditFormChangeOpDeserializer'
		);
	}

	public static function getGrammaticalFeaturesOrderProvider(): ItemOrderProvider {
		return MediaWikiServices::getInstance()->getService(
			'WikibaseLexemeGrammaticalFeaturesOrderProvider'
		);
	}

}
