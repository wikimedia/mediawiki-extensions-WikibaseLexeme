<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Rdf;

use MediaWiki\Revision\SlotRecord;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Presentation\Rdf\LexemeSpecificComponentsRdfBuilder;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Rdf\EntityMentionListener;
use Wikibase\Repo\Rdf\EntityStubRdfBuilderFactory;
use Wikibase\Repo\Rdf\HashDedupeBag;
use Wikibase\Repo\Rdf\NullEntityMentionListener;
use Wikibase\Repo\Rdf\RdfBuilder;
use Wikibase\Repo\Rdf\RdfProducer;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikibase\Repo\Tests\Rdf\RdfBuilderTestData;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Lexeme\Presentation\Rdf\LexemeSpecificComponentsRdfBuilder
 *
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeSpecificComponentsRdfBuilderTest extends TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	/**
	 * @var RdfBuilderTestData|null
	 */
	private $testData = null;

	protected function setUp(): void {
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
				__DIR__ . '/../../data/rdf/LexemeSpecificComponentsRdfBuilder'
			);
		}

		return $this->testData;
	}

	private function getVocabulary(): RdfVocabulary {
		return new RdfVocabulary(
			[ 'test' => 'http://acme.test/' ],
			[ 'test' => '' ],
			new EntitySourceDefinitions(
				[
					new DatabaseEntitySource(
						'test',
						'testdb',
						[
							'item' => [ 'namespaceId' => 500, 'slot' => SlotRecord::MAIN ],
							'lexeme' => [ 'namespaceId' => 700, 'slot' => SlotRecord::MAIN ],
						],
						'http://acme.test/',
						'',
						'',
						''
					),
				],
				new SubEntityTypesMapper( [
					'lexeme' => [ 'form', 'sense' ],
				] )
			),
			[ 'test' => '' ],
			[ 'test' => '' ],
			[],
			[],
			[],
			'http://creativecommons.org/publicdomain/zero/1.0/'
		);
	}

	/**
	 * @param RdfWriter $writer
	 * @param EntityMentionListener $entityMentionTracker
	 *
	 * @return LexemeSpecificComponentsRdfBuilder
	 */
	private function newBuilder( RdfWriter $writer, EntityMentionListener $entityMentionTracker ) {
		$vocabulary = $this->getVocabulary();
		$builder = new LexemeSpecificComponentsRdfBuilder(
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
	 *
	 * @return RdfBuilder
	 */
	private function newFullBuilder(
		RdfWriter $writer, $produce
	) {
		$builder = new RdfBuilder(
			$this->getVocabulary(),
			WikibaseRepo::getEntityRdfBuilderFactory(),
			$produce,
			$writer,
			new HashDedupeBag(),
			$this->createMock( EntityContentFactory::class ),
			$this->createMock( EntityStubRdfBuilderFactory::class ),
			$this->createMock( EntityRevisionLookup::class )
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
		$mentionTracker = $this->createMock( EntityMentionListener::class );
		$mentionTracker->method( 'subEntityMentioned' )
			->will( $this->returnCallback( static function ( EntityDocument $entity ) use ( &$subEntities ) {
				$subEntities[] = $entity->getId()->getSerialization();
			} ) );

		$writer = $this->getTestData()->getNTriplesWriter( false );
		$builder = $this->newBuilder( $writer, $mentionTracker );

		$builder->addEntity( $entity );
		$this->assertEquals( $expectedSubEntities, $subEntities );
	}

	private function mentionedEntityTest( EntityDocument $entity, $expectedMentionedEntities ) {
		$mentionedEntities = [];
		$mentionTracker = $this->createMock( EntityMentionListener::class );
		$mentionTracker->method( 'entityReferenceMentioned' )
			->will( $this->returnCallback( static function ( EntityId $id ) use ( &$mentionedEntities ) {
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

		$builder = $this->newFullBuilder( $writer, RdfProducer::PRODUCE_ALL );
		$builder->addEntity( $lexeme );
		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}
}
