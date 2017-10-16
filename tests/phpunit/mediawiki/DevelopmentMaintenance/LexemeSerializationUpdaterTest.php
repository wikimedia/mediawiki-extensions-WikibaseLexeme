<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\DevelopmentMaintenance;

use Wikibase\Lexeme\DevelopmentMaintenance\LexemeSerializationUpdater;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers Wikibase\Lexeme\DevelopmentMaintenance\LexemeSerializationUpdater
 *
 * @license GPL-2.0+
 */
class LexemeSerializationUpdaterTest extends \PHPUnit_Framework_TestCase {

	public function testGivenLexemeDataWithoutFormsField_fieldIsAdded() {
		$db = $this->getDB( [ $this->getLexemeDataWithNoFormsField() ] );

		$updater = $this->newUpdater( $db );

		$updater->update();

		$updatedData = $db->getUpdateData();

		$this->assertCount( 1, $updatedData );
		$this->assertEquals( [ 'old_id' => 1 ], $updatedData[0]['conds'] );
		$this->assertEquals(
			$this->getLexemeDataWithNextFormIdButNoForms(),
			json_decode( $updatedData[0]['new']['old_text'], true )
		);
	}

	public function testGivenLexemeDataWithoutNextFormIdField_fieldIsAdded() {
		$db = $this->getDB( [ $this->getLexemeDataWithFormsAndNoNextFormId() ] );

		$updater = $this->newUpdater( $db );

		$updater->update();

		$updatedData = $db->getUpdateData();

		$this->assertCount( 1, $updatedData );
		$this->assertEquals( [ 'old_id' => 1 ], $updatedData[0]['conds'] );
		$this->assertEquals(
			$this->getLexemeDataWithNextFormId(),
			json_decode( $updatedData[0]['new']['old_text'], true )
		);
	}

	public function testGivenLexemeDataWithNoFormsAndNoNextFormIdField_fieldIsAdded() {
		$db = $this->getDB( [ $this->getLexemeDataWithNoFormsAndNoNextFormId() ] );

		$updater = $this->newUpdater( $db );

		$updater->update();

		$updatedData = $db->getUpdateData();

		$this->assertCount( 1, $updatedData );
		$this->assertEquals(
			$this->getLexemeDataWithNextFormIdButNoForms(),
			json_decode( $updatedData[0]['new']['old_text'], true )
		);
	}

	public function testGivenLexemeDataStructureIsUpToDate_noUpdateDone() {
		$db = $this->getDB( [ $this->getLexemeDataWithNextFormId() ] );

		$updater = $this->newUpdater( $db );

		$updater->update();

		$updatedData = $db->getUpdateData();

		$this->assertEmpty( $updatedData );
	}

	public function testGivenLexemeDataWithoutSensesField_fieldIsAdded() {
		$db = $this->getDB( [ $this->getLexemeDataWithNoSensesField() ] );

		$updater = $this->newUpdater( $db );

		$updater->update();

		$updatedData = $db->getUpdateData();

		$this->assertCount( 1, $updatedData );
		$this->assertEquals( [ 'old_id' => 1 ], $updatedData[0]['conds'] );
		$this->assertEquals(
			$this->getLexemeDataWithNextFormIdButNoForms(),
			json_decode( $updatedData[0]['new']['old_text'], true )
		);
	}

	private function getLexemeDataWithFormsAndNoNextFormId() {
		return [
			'id' => 'L1',
			'type' => 'lexeme',
			'lemmas' => [
				[ 'en' => [ 'language' => 'en', 'value' => 'goat' ] ],
			],
			'language' => 'Q1',
			'lexicalCategory' => 'Q2',
			'claims' => [],
			'forms' => [
				[
					'id' => 'F2',
					'representations' => [
						[ 'en' => [ 'language' => 'en', 'value' => 'goat' ] ],
					],
					'grammaticalFeatures' => 'Q3',
					'claims' => [],
				]
			],
			'senses' => [],
		];
	}

	private function getLexemeDataWithNoFormsAndNoNextFormId() {
		$data = $this->getLexemeDataWithFormsAndNoNextFormId();

		$data['forms'] = [];

		return $data;
	}

	private function getLexemeDataWithNoFormsField() {
		$data = $this->getLexemeDataWithNextFormIdButNoForms();

		unset( $data['forms'] );

		return $data;
	}

	private function getLexemeDataWithNoSensesField() {
		$data = $this->getLexemeDataWithNextFormIdButNoForms();

		unset( $data['senses'] );

		return $data;
	}

	private function getLexemeDataWithNextFormId() {
		return [
			'id' => 'L1',
			'type' => 'lexeme',
			'lemmas' => [
				[ 'en' => [ 'language' => 'en', 'value' => 'goat' ] ],
			],
			'language' => 'Q1',
			'lexicalCategory' => 'Q2',
			'claims' => [],
			'nextFormId' => 3,
			'forms' => [
				[
					'id' => 'F2',
					'representations' => [
						[ 'en' => [ 'language' => 'en', 'value' => 'goat' ] ],
					],
					'grammaticalFeatures' => 'Q3',
					'claims' => [],
				]
			],
			'senses' => [],
		];
	}

	private function getLexemeDataWithNextFormIdButNoForms() {
		$data = $this->getLexemeDataWithNextFormId();

		$data['forms'] = [];
		$data['nextFormId'] = 1;

		return $data;
	}

	private function newUpdater( IDatabase $db ) {
		return new LexemeSerializationUpdater( $db, 666 );
	}

	private function getDB( array $data ) {
		$selectReturnRows = [];

		foreach ( $data as $index => $entry ) {
			$selectReturnRows[] = (object)[
				'rev_id' => $index + 1,
				'old_id' => $index + 1,
				'old_text' => json_encode( $entry ),
			];
		}

		return new DatabaseSpy( new FakeResultWrapper( $selectReturnRows ), 1 );
	}

}
