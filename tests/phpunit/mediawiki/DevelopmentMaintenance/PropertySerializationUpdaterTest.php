<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\DevelopmentMaintenance;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DevelopmentMaintenance\PropertySerializationUpdater;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers \Wikibase\Lexeme\DevelopmentMaintenance\PropertySerializationUpdater
 *
 * @see LexemeSerializationUpdaterTest
 *
 * @license GPL-2.0-or-later
 */
class PropertySerializationUpdaterTest extends TestCase {

	/**
	 * @dataProvider provideOldNewDatatypePair
	 */
	public function testGivenPropertyDataWithOldDataTypeId_idIsUpdated( $oldDatatype, $newDatatype ) {
		$db = $this->getDB( [ $this->getPropertyDataWithDataType( $oldDatatype ) ] );

		$updater = $this->newUpdater( $db );

		$updater->update();

		$updatedData = $db->getUpdateData();

		$this->assertCount( 2, $updatedData );

		$this->assertEquals( [ 'pi_property_id' => 123 ], $updatedData[0]['conds'] );
		$this->assertEquals(
			[
				'pi_type' => $newDatatype,
				'pi_info' => json_encode( [ 'type' => $newDatatype ] ),
			],
			$updatedData[0]['new']
		);

		$this->assertEquals( [ 'old_id' => 1 ], $updatedData[1]['conds'] );
		$this->assertEquals(
			$this->getPropertyDataWithDataType( $newDatatype ),
			json_decode( $updatedData[1]['new']['old_text'], true )
		);
	}

	private function getPropertyDataWithDataType( $datatype ) {
		return [
			'id' => 'P123',
			'datatype' => $datatype,
			'type' => 'property',
			'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'some property' ] ],
			'claims' => [],
		];
	}

	private function newUpdater( IDatabase $db ) {
		return new PropertySerializationUpdater( $db, 666 );
	}

	private function getDB( array $data ) {
		$selectReturnRows = [];

		foreach ( $data as $index => $entry ) {
			$selectReturnRows[] = (object)[
				'page_title' => 'P' . $index,
				'rev_id' => $index + 1,
				'old_id' => $index + 1,
				'old_text' => json_encode( $entry ),
			];
		}

		return $this->getMockForAbstractClass(
			DatabaseSpy::class,
			[
				new FakeResultWrapper( $selectReturnRows ),
				1
			]
		);
	}

	public function provideOldNewDatatypePair() {
		return [
			[ 'wikibase-lexeme-form', 'wikibase-form' ],
			[ 'wikibase-lexeme-sense', 'wikibase-sense' ],
		];
	}

}
