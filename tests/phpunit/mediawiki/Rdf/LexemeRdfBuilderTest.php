<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Rdf;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\Domain\DataModel\FormId;
use Wikibase\Lexeme\Domain\DataModel\SenseId;
use Wikibase\Lexeme\Rdf\LexemeRdfBuilder;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Rdf\EntityMentionListener;
use Wikibase\Rdf\HashDedupeBag;
use Wikibase\Rdf\NullEntityMentionListener;
use Wikibase\Rdf\RdfBuilder;
use Wikibase\Rdf\RdfProducer;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikibase\Repo\Tests\Rdf\RdfBuilderTestData;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Lexeme\Rdf\LexemeRdfBuilder
 *
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeRdfBuilderTest extends TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	/**
	 * @var RdfBuilderTestData|null
	 */
	private $testData = null;

	protected function setUp() {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	/**
	 * Initialize repository data
	 *
	 * @return RdfBuilderTestData
	 */
	private function getTestData() {
		if ( $this->testData === null ) {
			$this->testData = new RdfBuilderTestData(
				__DIR__ . '/../../data/rdf/entities',
				__DIR__ . '/../../data/rdf/LexemeRdfBuilder'
			);
		}

		return $this->testData;
	}

	/**
	 * @param RdfWriter $writer
	 * @param EntityMentionListener $entityMentionTracker
	 *
	 * @return LexemeRdfBuilder
	 */
	private function newBuilder( RdfWriter $writer, EntityMentionListener $entityMentionTracker ) {
		$vocabulary = $this->getTestData()->getVocabulary();
		$builder = new LexemeRdfBuilder(
			$vocabulary,
			$writer,
			$entityMentionTracker
		);
		$builder->addPrefixes();
		$writer->start();
		return $builder;
	}

	/**
	 * @param RdfWriter $writer
	 * @param int $produce One of the RdfProducer::PRODUCE_... constants.
	 * @param EntityTitleLookup $entityTitleLookup
	 *
	 * @return RdfBuilder
	 */
	private function newFullBuilder(
		RdfWriter $writer, $produce, EntityTitleLookup $entityTitleLookup
	) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$builder = new RdfBuilder(
			$this->getTestData()->getSiteLookup()->getSites(),
			$this->getTestData()->getVocabulary(),
			$wikibaseRepo->getValueSnakRdfBuilderFactory(),
			$this->getTestData()->getMockRepository(),
			$wikibaseRepo->getEntityRdfBuilderFactory(),
			$produce,
			$writer,
			new HashDedupeBag(),
			$entityTitleLookup
		);
		$builder->startDocument();
		return $builder;
	}

	private function assertOrCreateNTriples( $dataSetName, RdfWriter $writer ) {
		$actual = $writer->drain();
		$expected = $this->getTestData()->getNTriples( $dataSetName );

		if ( $expected === null ) {
			$this->getTestData()->putTestData( $dataSetName, $actual, '.actual' );
			$this->fail( "Data set $dataSetName not found! Created file with the current data "
				. 'using the suffix .actual' );
		}

		$this->helper->assertNTriplesEquals( $expected, $actual, "Data set $dataSetName" );
	}

	public function provideAddLexeme() {
		return [
			[ 'L2', 'L2_all' ],
		];
	}

	/**
	 * @dataProvider provideAddLexeme
	 */
	public function testAddLexeme( $lexemeName, $dataSetName ) {
		$lexeme = $this->getTestData()->getEntity( $lexemeName );
		$this->addEntityTest( $lexeme, $dataSetName );
	}

	public function provideLexemeSubEntities() {
		return [
			[ 'L2', [ 'L2-F1', 'L2-S1' ] ],
		];
	}

	/**
	 * @dataProvider provideLexemeSubEntities
	 */
	public function testLexemeSubEntities( $lexemeName, $subEntities ) {
		$lexeme = $this->getTestData()->getEntity( $lexemeName );
		$this->subEntityTest( $lexeme, $subEntities );
	}

	public function provideLexemeMentionedEntities() {
		return [
			[ 'L2', [ 'Q1', 'Q2' ] ],
		];
	}

	/**
	 * @dataProvider provideLexemeMentionedEntities
	 */
	public function testLexemeMentionedEntities( $lexemeName, $mentionedEntities ) {
		$lexeme = $this->getTestData()->getEntity( $lexemeName );
		$this->mentionedEntityTest( $lexeme, $mentionedEntities );
	}

	public function provideAddForm() {
		return [
			[ 'L2', 'L2-F1', 'L2-F1_all' ],
		];
	}

	/**
	 * @dataProvider provideAddForm
	 */
	public function testAddForm( $lexemeName, $formName, $dataSetName ) {
		$lexeme = $this->getTestData()->getEntity( $lexemeName );
		$form = $lexeme->getForm( new FormId( $formName ) );
		$this->addEntityTest( $form, $dataSetName );
	}

	public function provideFormMentionedEntities() {
		return [
			[ 'L2', 'L2-F1', [ 'Q3', 'Q4' ] ],
		];
	}

	/**
	 * @dataProvider provideFormMentionedEntities
	 */
	public function testFormMentionedEntities( $lexemeName, $formName, $mentionedEntities ) {
		$lexeme = $this->getTestData()->getEntity( $lexemeName );
		$form = $lexeme->getForm( new FormId( $formName ) );
		$this->mentionedEntityTest( $form, $mentionedEntities );
	}

	public function provideAddSense() {
		return [
			[ 'L2', 'L2-S1', 'L2-S1_all' ],
		];
	}

	/**
	 * @dataProvider provideAddSense
	 */
	public function testAddSense( $lexemeName, $senseName, $dataSetName ) {
		$lexeme = $this->getTestData()->getEntity( $lexemeName );
		$sense = $lexeme->getSense( new SenseId( $senseName ) );
		$this->addEntityTest( $sense, $dataSetName );
	}

	public function provideSenseMentionedEntities() {
		return [
			[ 'L2', 'L2-S1', [] ],
		];
	}

	/**
	 * @dataProvider provideSenseMentionedEntities
	 */
	public function testSenseMentionedEntities( $lexemeName, $senseName, $mentionedEntities ) {
		$lexeme = $this->getTestData()->getEntity( $lexemeName );
		$sense = $lexeme->getSense( new SenseId( $senseName ) );
		$this->mentionedEntityTest( $sense, $mentionedEntities );
	}

	private function addEntityTest( EntityDocument $entity, $dataSetName ) {
		$writer = $this->getTestData()->getNTriplesWriter( false );
		$builder = $this->newBuilder( $writer, new NullEntityMentionListener() );

		$builder->addEntity( $entity );
		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

	private function subEntityTest( EntityDocument $entity, $expectedSubEntities ) {
		$subEntities = [];
		$mentionTracker = $this->getMock( EntityMentionListener::class );
		$mentionTracker->expects( $this->any() )
			->method( 'subEntityMentioned' )
			->will( $this->returnCallback( function( EntityDocument $entity ) use ( &$subEntities ) {
				$subEntities[] = $entity->getId()->getSerialization();
			} ) );

		$writer = $this->getTestData()->getNTriplesWriter( false );
		$builder = $this->newBuilder( $writer, $mentionTracker );

		$builder->addEntity( $entity );
		$this->assertEquals( $expectedSubEntities, $subEntities );
	}

	private function mentionedEntityTest( EntityDocument $entity, $expectedMentionedEntities ) {
		$mentionedEntities = [];
		$mentionTracker = $this->getMock( EntityMentionListener::class );
		$mentionTracker->expects( $this->any() )
			->method( 'entityReferenceMentioned' )
			->will( $this->returnCallback( function( EntityId $id ) use ( &$mentionedEntities ) {
				$mentionedEntities[] = $id->getSerialization();
			} ) );

		$writer = $this->getTestData()->getNTriplesWriter( false );
		$builder = $this->newBuilder( $writer, $mentionTracker );

		$builder->addEntity( $entity );
		$this->assertEquals( $expectedMentionedEntities, $mentionedEntities );
	}

	public function provideLexemeFullSerialization() {
		return [
			[ 'L2', 'L2_full' ],
		];
	}

	/**
	 * @dataProvider provideLexemeFullSerialization
	 */
	public function testLexemeFullSerialization( $lexemeName, $dataSetName ) {
		$lexeme = $this->getTestData()->getEntity( $lexemeName );
		$writer = $this->getTestData()->getNTriplesWriter( false );
		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );

		$builder = $this->newFullBuilder( $writer, RdfProducer::PRODUCE_ALL, $entityTitleLookup );
		$builder->addEntity( $lexeme );
		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

	public function provideAddLexemeStub() {
		return [
			[ 'L2', 'L2_stubs' ],
		];
	}

	/**
	 * @dataProvider provideAddLexemeStub
	 */
	public function testAddLexemeStub( $lexemeName, $dataSetName ) {
		$lexeme = $this->getTestData()->getEntity( $lexemeName );
		$this->addEntityStubTest( $lexeme, $dataSetName );
	}

	public function provideAddFormStub() {
		return [
			[ 'L2', 'L2-F1', 'L2-F1_stubs' ],
		];
	}

	/**
	 * @dataProvider provideAddFormStub
	 */
	public function testAddFormStub( $lexemeName, $formName, $dataSetName ) {
		$lexeme = $this->getTestData()->getEntity( $lexemeName );
		$form = $lexeme->getForm( new FormId( $formName ) );
		$this->addEntityStubTest( $form, $dataSetName );
	}

	public function provideAddSenseStub() {
		return [
			[ 'L2', 'L2-S1', 'L2-S1_stubs' ],
		];
	}

	/**
	 * @dataProvider provideAddSenseStub
	 */
	public function testAddSenseStub( $lexemeName, $senseName, $dataSetName ) {
		$lexeme = $this->getTestData()->getEntity( $lexemeName );
		$sense = $lexeme->getSense( new SenseId( $senseName ) );
		$this->addEntityStubTest( $sense, $dataSetName );
	}

	private function addEntityStubTest( EntityDocument $entity, $dataSetName ) {
		$writer = $this->getTestData()->getNTriplesWriter( false );
		$builder = $this->newBuilder( $writer, new NullEntityMentionListener() );

		$builder->addEntityStub( $entity );
		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

}
