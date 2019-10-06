<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Content;

use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\MediaWiki\Content\LexemeContent;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lexeme\MediaWiki\Content\LexemeHandler;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Search\Fields\NoFieldDefinitions;
use Wikibase\Repo\Tests\Content\EntityHandlerTestCase;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;
use Wikibase\Store\EntityIdLookup;
use Wikibase\TermIndex;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Content\LexemeHandler
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LexemeHandlerTest extends EntityHandlerTestCase {

	/**
	 * @return string
	 */
	public function getModelId() {
		return LexemeContent::CONTENT_MODEL_ID;
	}

	/**
	 * @param SettingsArray|null $settings
	 *
	 * @return EntityHandler
	 */
	protected function getHandler( SettingsArray $settings = null ) {
		return $this->getWikibaseRepo( $settings )
			->getEntityContentFactory()
			->getContentHandlerForType( Lexeme::ENTITY_TYPE );
	}

	/**
	 * @param EntityId|null $id
	 *
	 * @return EntityDocument
	 */
	protected function newEntity( EntityId $id = null ) {
		if ( !$id ) {
			$id = new LexemeId( 'L7' );
		}

		$lexeme = new Lexeme( $id );
		$lexeme->setLemmas(
			new TermList(
				[
					new Term( 'en', 'goat' ),
					new Term( 'de', 'Ziege' ),
				]
			)
		);
		$lexeme->setLanguage( new ItemId( 'Q123' ) );
		$lexeme->setLexicalCategory( new ItemId( 'Q567' ) );

		return $lexeme;
	}

	/**
	 * Returns EntityContents that can be serialized by the EntityHandler deriving class.
	 *
	 * @return array[]
	 */
	public function contentProvider() {
		$content = $this->newEntityContent();

		return [
			[ $content ],
		];
	}

	/**
	 * @return array
	 */
	public function entityIdProvider() {
		return [
			[ 'L7' ],
		];
	}

	/**
	 * @return array
	 */
	protected function getExpectedSearchIndexFields() {
		return [];
	}

	/**
	 * @return LexemeContent
	 */
	protected function getTestContent() {
		return $this->newEntityContent();
	}

	protected function getEntityTypeDefinitions() {
		return new EntityTypeDefinitions(
			array_merge_recursive(
				require __DIR__ . '/../../../../WikibaseLexeme.entitytypes.php',
				require __DIR__ . '/../../../../WikibaseLexeme.entitytypes.repo.php'
			)
		);
	}

	protected function getEntitySerializer() {
		$baseModelSerializerFactory = WikibaseRepo::getDefaultInstance()
			->getBaseDataModelSerializerFactory();
		$entityTypeDefinitions = $this->getEntityTypeDefinitions();
		$serializerFactoryCallbacks = $entityTypeDefinitions->getSerializerFactoryCallbacks();
		return $serializerFactoryCallbacks['lexeme']( $baseModelSerializerFactory );
	}

	private function getMockWithoutConstructor( $className ) {
		return $this->getMockBuilder( $className )
			->disableOriginalConstructor()
			->getMock();
	}

	private function newLexemeHandler() {
		$labelLookupFactory = $this->getMockWithoutConstructor(
			LanguageFallbackLabelDescriptionLookupFactory::class
		);
		$labelLookupFactory->method( 'newLabelDescriptionLookup' )
			->will( $this->returnValue( $this->createMock( LabelDescriptionLookup::class ) ) );

		return new LexemeHandler(
			$this->createMock( TermIndex::class ),
			$this->getMockWithoutConstructor( EntityContentDataCodec::class ),
			$this->getMockWithoutConstructor( EntityConstraintProvider::class ),
			$this->createMock( ValidatorErrorLocalizer::class ),
			$this->createMock( EntityIdParser::class ),
			$this->createMock( EntityIdLookup::class ),
			$this->createMock( EntityLookup::class ),
			$labelLookupFactory,
			new NoFieldDefinitions()
		);
	}

	public function testAllowAutomaticIds() {
		$lexemeHandler = $this->newLexemeHandler();

		$this->assertTrue( $lexemeHandler->allowAutomaticIds() );
	}

	public function testCanCreateWithCustomId() {
		$lexemeHandler = $this->newLexemeHandler();

		$this->assertFalse( $lexemeHandler->canCreateWithCustomId( new LexemeId( 'L1' ) ) );
	}

	public function testDataForSearchIndex() {
		$handler = $this->getHandler();
		$engine = $this->createMock( \SearchEngine::class );

		$page = $this->getMockWikiPage( $handler );

		$data = $handler->getDataForSearchIndex( $page, new \ParserOutput(), $engine );
		$this->assertSame( LexemeContent::CONTENT_MODEL_ID, $data['content_model'], 'content_model' );
	}

	public function testExportTransform( $blob = null, $expected = null ) {
		$this->markTestSkipped( 'serialized data transformation issues are irrelevant to Lexemes' );
	}

	public function testGivenLexemePageGetIdForTitle_returnsLexemeId() {
		$handler = $this->getHandler();

		$this->assertEquals(
			new LexemeId( 'L1' ),
			$handler->getIdForTitle( Title::makeTitle( 5000, 'L1' ) )
		);
	}

	public function testGivenLexemePageWithFormIdFragmentGetIdForTitle_returnsFormId() {
		$handler = $this->getHandler();

		$this->assertEquals(
			new FormId( 'L1-F2' ),
			$handler->getIdForTitle( Title::makeTitle( 5000, 'L1', 'L1-F2' ) )
		);
	}

	public function testGivenLexemePageWithSenseIdFragmentGetIdForTitle_returnsSenseId() {
		$handler = $this->getHandler();

		$this->assertEquals(
			new SenseId( 'L1-S2' ),
			$handler->getIdForTitle( Title::makeTitle( 5000, 'L1', 'L1-S2' ) )
		);
	}

	public function testGivenLexemePageWithFormFragmentGetIdForTitle_returnsFormId() {
		$handler = $this->getHandler();

		$this->assertEquals(
			new FormId( 'L1-F3' ),
			$handler->getIdForTitle( Title::makeTitle( 5000, 'L1', 'F3' ) )
		);
	}

	public function testGivenLexemePageWithSenseFragmentGetIdForTitle_returnsSenseId() {
		$handler = $this->getHandler();

		$this->assertEquals(
			new SenseId( 'L1-S3' ),
			$handler->getIdForTitle( Title::makeTitle( 5000, 'L1', 'S3' ) )
		);
	}

	public function testGivenLexemePageWithOtherFormIdFragmentGetForTitle_returnsLexemeId() {
		$handler = $this->getHandler();

		$this->assertEquals(
			new LexemeId( 'L1' ),
			$handler->getIdForTitle( Title::makeTitle( 5000, 'L1', 'L2-F2' ) )
		);
	}

	public function testGivenLexemePageWithOtherSenseIdFragmentGetForTitle_returnsLexemeId() {
		$handler = $this->getHandler();

		$this->assertEquals(
			new LexemeId( 'L1' ),
			$handler->getIdForTitle( Title::makeTitle( 5000, 'L1', 'L2-S2' ) )
		);
	}

	public function testSupportsRedirects() {
		$this->assertTrue( $this->getHandler()->supportsRedirects() );
	}

}
