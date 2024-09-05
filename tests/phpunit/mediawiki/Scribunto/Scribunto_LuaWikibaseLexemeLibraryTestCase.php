<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Scribunto;

use ExtensionRegistry;
use MediaWiki\MainConfigNames;
use PHPUnit\Framework\TestSuite;
use Wikibase\Client\Tests\Integration\DataAccess\Scribunto\Scribunto_LuaWikibaseLibraryTestCase;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lib\Tests\MockRepository;
use Wikimedia\TestingAccessWrapper;

if (
	!ExtensionRegistry::getInstance()->isLoaded( 'WikibaseClient' ) ||
	!ExtensionRegistry::getInstance()->isLoaded( 'Scribunto' )
) {
	/**
	 * Fake base class in case Scribunto or Wikibase client is not available.
	 */
	abstract class Scribunto_LuaWikibaseLexemeLibraryTestCase extends \PHPUnit\Framework\TestCase {

		protected function setUp(): void {
			$this->markTestSkipped( 'WikibaseClient and Scribunto extensions are needed to run the tests' );
		}

		public function testPlaceholder() {
			$this->fail( 'PHPunit expects this class to have tests. This should never run.' );
		}

	}

	return;
}

/**
 * @license GPL-2.0-or-later
 */
class Scribunto_LuaWikibaseLexemeLibraryTestCase extends Scribunto_LuaWikibaseLibraryTestCase {

	/** @var bool */
	private static $originalLexemeEnableDataTransclusion;

	/**
	 * Make sure data transclusion is enabled regardless of wiki configuration.
	 */
	private static function enableDataTransclusion() {
		global $wgLexemeEnableDataTransclusion;
		self::$originalLexemeEnableDataTransclusion = $wgLexemeEnableDataTransclusion;
		$wgLexemeEnableDataTransclusion = true;
	}

	private static function resetDataTransclusion() {
		global $wgLexemeEnableDataTransclusion;
		$wgLexemeEnableDataTransclusion = self::$originalLexemeEnableDataTransclusion;
	}

	/**
	 * Set up stuff we need to have in place even before Scribunto does its stuff.
	 * And remove that again after suite is done, so that other test won't get
	 * affected.
	 *
	 * @param string $className
	 *
	 * @return TestSuite
	 */
	public static function suite( $className ) {
		self::enableDataTransclusion();

		$res = parent::suite( $className );

		self::resetDataTransclusion();

		return $res;
	}

	protected function setUp(): void {
		parent::setUp();

		self::enableDataTransclusion();

		$this->overrideConfigValue( MainConfigNames::LanguageCode, 'en' );

		/** @var MockRepository $mockRepository */
		$mockRepository = WikibaseClient::getStore()->getSiteLinkLookup();
		$lexeme = NewLexeme::havingId( 'L1' )
			->withLemma( 'en', 'English lemma' )
			->withLemma( 'en-gb', 'British English lemma' )
			->withLanguage( 'Q1' )
			->withLexicalCategory( 'Q2' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'English representation' )
					->andRepresentation( 'en-gb', 'British English representation' )
					->andGrammaticalFeature( 'Q1' )
			)
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'en', 'English gloss' )
					->withGloss( 'en-gb', 'British English gloss' )
			)
			->build();
		$mockRepository->putEntity( $lexeme );
		foreach ( $lexeme->getForms()->toArrayUnordered() as $form ) {
			$mockRepository->putEntity( $form );
		}
		foreach ( $lexeme->getSenses()->toArrayUnordered() as $sense ) {
			$mockRepository->putEntity( $sense );
		}
	}

	protected function newScribuntoLuaWikibaseLexemeLibrary( string $klass ) {
		$engine = $this->getEngine();
		$engine->load();
		return new $klass( $engine );
	}

	private static function getParserOutputFromRedirectUsageAccumulator( $redirectUsageAccumulator ) {
		$innerAccumulator = TestingAccessWrapper::newFromObject( $redirectUsageAccumulator )->innerUsageAccumulator;
		return TestingAccessWrapper::newFromObject( $innerAccumulator )->getParserOutput();
	}

	public function makeParserOutputUsageAccumulatorAssertions( string $klass ): void {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLexemeLibrary( $klass );
		$libraryWithMemberAccess = TestingAccessWrapper::newFromObject( $luaWikibaseLibrary );
		$parserOutput = $libraryWithMemberAccess->getParser()->getOutput();
		$usageAccumulator = $libraryWithMemberAccess->getUsageAccumulator();
		$this->assertSame(
			$parserOutput,
			self::getParserOutputFromRedirectUsageAccumulator( $usageAccumulator ),
			"Current engine parser output should be used by usage accumulator" );
		$libraryWithMemberAccess->getParser()->resetOutput();
		$newUsageAccumulator = $libraryWithMemberAccess->getUsageAccumulator();
		$this->assertSame( $usageAccumulator, $newUsageAccumulator,
			"Usage accumulator should not be reconstructed after parser output reset" );
		$newParserOutput = $libraryWithMemberAccess->getParser()->getOutput();
		$this->assertNotSame( $newParserOutput, $parserOutput,
			"Engine should have a new parser output after a reset" );
		$this->assertSame(
			$newParserOutput,
			self::getParserOutputFromRedirectUsageAccumulator( $newUsageAccumulator ),
			"Usage accumulator should be using the new parser output" );
	}

	protected function tearDown(): void {
		self::resetDataTransclusion();

		parent::tearDown();
	}

}
