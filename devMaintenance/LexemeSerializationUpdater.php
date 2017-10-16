<?php

namespace Wikibase\Lexeme\DevelopmentMaintenance;

use Wikimedia\Rdbms\IDatabase;

/**
 * Converts lexeme serialized in a possible outdated format to the up-do-date form.
 * To be e.g. to "fix" old lexeme data in the database when the serialization format
 * has changed.
 *
 * This is NOT supposed to be used in production! It is only for convenience when
 * doing changes during the development phase.
 *
 * @license GPL-2.0+
 */
class LexemeSerializationUpdater {

	private $batchSize = 1000;

	private $position = 0;

	private $namespaceId;

	private $db;

	/**
	 * @param IDatabase $db
	 * @param int $namespaceId
	 */
	public function __construct( IDatabase $db, $namespaceId ) {
		$this->db = $db;
		$this->namespaceId = $namespaceId;
	}

	public function update() {
		while ( true ) {
			$lexemeData = $this->getSerializedLexemeData();

			if ( !$lexemeData ) {
				break;
			}

			$this->updateSerializedLexemeData( $lexemeData );
		}
	}

	private function getSerializedLexemeData() {
		$rows = $this->db->select(
			[ 'page', 'revision', 'text' ],
			[ 'rev_id', 'old_id', 'old_text' ],
			$this->getConds(),
			__METHOD__,
			[ 'LIMIT' => $this->batchSize, 'ORDER BY' => 'rev_id ASC' ]
		);

		$serializedData = [];
		$maxRevisionId = 0;

		foreach ( $rows as $row ) {
			$serializedData[] = [
				'id' => $row->old_id,
				'data' => $row->old_text,
			];

			if ( $row->rev_id > $maxRevisionId ) {
				$maxRevisionId = $row->rev_id;
			}
		}

		$this->position = $maxRevisionId;

		return $serializedData;
	}

	private function getConds() {
		return [
			'page_namespace' => $this->namespaceId,
			'rev_page = page_id',
			'rev_text_id = old_id',
			'rev_id > ' . (int)$this->position,
		];
	}

	private function updateSerializedLexemeData( array $lexemes ) {
		foreach ( $lexemes as $lexemeData ) {
			$blobId = $lexemeData['id'];
			$blobData = json_decode( $lexemeData['data'], true );
			$oldData = $blobData;

			$this->addFormsField( $blobData );
			$this->addSensesField( $blobData );
			$this->addNextFormIdField( $blobData );

			if ( $blobData !== $oldData ) {
				$newData = json_encode( $blobData );

				$this->db->update(
					'text',
					[ 'old_text' => $newData, ],
					[ 'old_id' => $blobId ],
					__METHOD__
				);
			}
		}
	}

	/**
	 * Adds a forms field if it is missing.
	 * Field has been added to the data in the commit
	 * 17b3a39325a3acb07a3d387af20f156d879dc0f9
	 */
	private function addFormsField( array &$data ) {
		if ( array_key_exists( 'forms', $data ) ) {
			return;
		}

		$data['forms'] = [];
	}

	/**
	 * Adds a senses field if it is missing.
	 * Field has been added to the data in the commit
	 * 8b839d39600890d68a11cad0945398a6a7534f5d
	 */
	private function addSensesField( array &$data ) {
		if ( array_key_exists( 'senses', $data ) ) {
			return;
		}

		$data['senses'] = [];
	}

	/**
	 * Adds a nextFormId field if it is missing.
	 * Field has been added to the data in the commit
	 * b2a4b7d0a52cd3a60056bbaf19cc0c012a1909e3
	 */
	private function addNextFormIdField( array &$data ) {
		if ( array_key_exists( 'nextFormId', $data ) ) {
			return;
		}

		// Not perfect but the best that could be done for the isolated revision data
		$maxFormId = $this->getMaxFormId( $data );

		$data['nextFormId'] = $maxFormId + 1;
	}

	private function getMaxFormId( array $data ) {
		return (int)array_reduce(
			$data['forms'],
			function ( $currentMaxId, $x ) {
				return max( $currentMaxId, (int)substr( $x['id'], 1 ) );
			},
			0
		);
	}

}
