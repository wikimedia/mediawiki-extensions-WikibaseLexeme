<?php

namespace Wikibase\Lexeme\DevelopmentMaintenance;

use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;
use Wikimedia\Rdbms\IDatabase;

/**
 * Converts lexeme serialized in a possible outdated format to the up-do-date form.
 * To be e.g. to "fix" old lexeme data in the database when the serialization format
 * has changed.
 *
 * This is NOT supposed to be used in production! It is only for convenience when
 * doing changes during the development phase.
 *
 * @license GPL-2.0-or-later
 */
class LexemeSerializationUpdater {

	private $batchSize = 1000;

	private $position = 0;

	private $namespaceId;

	private $db;

	private $reporter;

	/**
	 * @param IDatabase $db
	 * @param int $namespaceId
	 */
	public function __construct( IDatabase $db, $namespaceId ) {
		$this->db = $db;
		$this->namespaceId = $namespaceId;

		$this->reporter = new NullMessageReporter();
	}

	public function setMessageReporter( MessageReporter $reporter ) {
		$this->reporter = $reporter;
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
			[ 'page_title', 'rev_id', 'old_id', 'old_text' ],
			$this->getConds(),
			__METHOD__,
			[ 'LIMIT' => $this->batchSize, 'ORDER BY' => 'rev_id ASC' ]
		);

		$serializedData = [];
		$maxRevisionId = 0;

		foreach ( $rows as $row ) {
			$serializedData[] = [
				'lexemeId' => $row->page_title,
				'revisionId' => $row->rev_id,
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

			$this->prependFormIds( $blobData );
			$this->useFormIdInStatementId( $blobData );

			$this->addNextFormIdField( $blobData );

			if ( $blobData !== $oldData ) {
				$newData = json_encode( $blobData );

				$this->db->update(
					'text',
					[ 'old_text' => $newData, ],
					[ 'old_id' => $blobId ],
					__METHOD__
				);

				$this->reporter->reportMessage(
					'Updated: ' . $lexemeData['lexemeId'] . ' (rev: ' . $lexemeData['revisionId'] . ')'
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
			function ( $currentMaxId, $x ) use ( $data ) {
				$formIdPart = substr( $x['id'], strlen( $data['id'] . '-' ) );
				return max( $currentMaxId, (int)substr( $formIdPart, 1 ) );
			},
			0
		);
	}

	/**
	 * Prepends all form IDs with a lexeme ID.
	 * This change has been made to the data in the commit
	 * 6995fe11a7b637bf2301293c492b1fb3ff7a82a4.
	 */
	private function prependFormIds( array &$data ) {
		$lexemeId = $data['id'];

		foreach ( $data['forms'] as &$formData ) {
			if ( strncmp( $formData['id'], $lexemeId . '-', strlen( $lexemeId ) + 1 ) !== 0 ) {
				$formId = $formData['id'];
				$formIdParts = explode( '-', $formData['id'], 2 );
				if ( count( $formIdParts ) === 2 ) {
					$formId = $formIdParts[1];
				}

				$formData['id'] = $lexemeId . '-' . $formId;
			}
		}
	}

	/**
	 * Use form ID in IDs of all statementents on a form.
	 * This change has been made to the data in the commit
	 * 699517bbb992e8a73c20955603f3aafb998183f033f4.
	 */
	private function useFormIdInStatementId( array &$data ) {
		foreach ( $data['forms'] as &$formData ) {
			$formId = $formData['id'];
			foreach ( $formData['claims'] as &$statementsPerProperty ) {
				foreach ( $statementsPerProperty as &$statementData ) {
					list( $entityId, $guid ) = explode( '$', $statementData['id'], 2 );
					if ( $entityId !== $formId ) {
						$statementData['id'] = $formId . '$' . $guid;
					}
				}
			}
		}
	}

}
