<?php

declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\Rdf;

use MediaWiki\Revision\SlotRecord;
use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Presentation\Rdf\LexemeStubRdfBuilder;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikibase\Repo\Tests\Rdf\RdfBuilderTestData;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Lexeme\Presentation\Rdf\LexemeStubRdfBuilder
 *
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 */
class LexemeStubRdfBuilderTest extends MediaWikiIntegrationTestCase {

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
		$this->lookup = new InMemoryEntityLookup();
	}

	/**
	 * Initialize repository data
	 *
	 * @return RdfBuilderTestData
	 */
	private function getTestData(): RdfBuilderTestData {
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
					new DataBaseEntitySource(
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
	 *
	 * @return LexemeStubRdfBuilder
	 */
	private function newBuilder( RdfWriter $writer ): LexemeStubRdfBuilder {
		$vocabulary = $this->getVocabulary();

		$builder = new LexemeStubRdfBuilder(
			$vocabulary,
			$writer,
			$this->lookup
		);
		$builder->addPrefixes();
		$writer->start();
		return $builder;
	}

	private function assertOrCreateNTriples( string $dataSetName, RdfWriter $writer ): void {
		$actual = $writer->drain();
		$expected = $this->getTestData()->getNTriples( $dataSetName );

		if ( $expected === null ) {
			$this->getTestData()->putTestData( $dataSetName, $actual, '.actual' );
			$this->fail( "Data set $dataSetName not found! Created file with the current data "
				. 'using the suffix .actual' );
		}

		$this->helper->assertNTriplesEquals( $expected, $actual, "Data set $dataSetName" );
	}

	public function provideAddLexemeStub(): array {
		return [
			[ 'L2', 'L2_stubs' ],
		];
	}

	/**
	 * @dataProvider provideAddLexemeStub
	 */
	public function testAddLexemeStub( string $lexemeName, string $dataSetName ) {
		$lexeme = $this->getTestData()->getEntity( $lexemeName );
		$this->lookup->addEntity( $lexeme );
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
	public function testAddFormStub( string $lexemeName, string $formName, string $dataSetName ): void {
		$lexeme = $this->getTestData()->getEntity( $lexemeName );
		$form = $lexeme->getForm( new FormId( $formName ) );
		$this->lookup->addEntity( $form );
		$this->addEntityStubTest( $form, $dataSetName );
	}

	public function provideAddSenseStub(): array {
		return [
			[ 'L2', 'L2-S1', 'L2-S1_stubs' ],
		];
	}

	/**
	 * @dataProvider provideAddSenseStub
	 */
	public function testAddSenseStub( string $lexemeName, string $senseName, string $dataSetName ): void {
		$lexeme = $this->getTestData()->getEntity( $lexemeName );
		$sense = $lexeme->getSense( new SenseId( $senseName ) );
		$this->lookup->addEntity( $sense );
		$this->addEntityStubTest( $sense, $dataSetName );
	}

	private function addEntityStubTest( EntityDocument $entity, string $dataSetName ): void {
		$writer = $this->getTestData()->getNTriplesWriter( false );
		$builder = $this->newBuilder( $writer );
		$entityId = $entity->getId();
		$builder->addEntityStub( $entityId );
		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

}
