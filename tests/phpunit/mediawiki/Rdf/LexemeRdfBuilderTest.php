<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Rdf;

use Wikibase\Lexeme\Rdf\LexemeRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikibase\Repo\Tests\Rdf\RdfBuilderTestData;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Lexeme\Rdf\LexemeRdfBuilder
 *
 * @group WikibaseRdf
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeRdfBuilderTest extends \PHPUnit_Framework_TestCase {

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
	 *
	 * @return LexemeRdfBuilder
	 */
	private function newBuilder( RdfWriter $writer ) {
		$vocabulary = $this->getTestData()->getVocabulary();

		return new LexemeRdfBuilder(
			$vocabulary,
			$writer
		);
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

	public function provideAddEntity() {
		return [
			[ 'L2', 'L2_all' ],
		];
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddEntity( $entityName, $dataSetName ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$this->newBuilder( $writer )->addEntity( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

	public function provideAddEntityStub() {
		return [
			[ 'L2', 'L2_stubs' ],
		];
	}

	/**
	 * @dataProvider provideAddEntityStub
	 */
	public function testAddEntityStub( $entityName, $dataSetName ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$this->newBuilder( $writer )->addEntityStub( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

	public function provideAddLemmas() {
		return [
			[ 'L2', 'L2_lemmas' ],
		];
	}

	/**
	 * @dataProvider provideAddLemmas
	 */
	public function testAddLemmas( $entityName, $dataSetName ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$this->newBuilder( $writer )
			->addLemmas( $entity->getId(), $entity->getLemmas() );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

}
