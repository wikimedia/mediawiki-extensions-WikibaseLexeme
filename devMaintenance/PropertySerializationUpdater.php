<?php

namespace Wikibase\Lexeme\DevelopmentMaintenance;

use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;
use Wikimedia\Rdbms\IDatabase;

/**
 * Converts lexeme related properties serialized in a possible outdated format to
 * the up-do-date form. To be e.g. to "fix" old property data in the database when
 * the serialization format has changed.
 *
 * This is NOT supposed to be used in production! It is only for convenience when
 * doing changes during the development phase.
 *
 * @license GPL-2.0-or-later
 */
class PropertySerializationUpdater {

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
			$propertyData = $this->getSerializedPropertyData();

			if ( !$propertyData ) {
				break;
			}

			$this->updateSerializedPropertyData( $propertyData );
		}
	}

	private function getSerializedPropertyData() {
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
				'propertyId' => $row->page_title,
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

	private function updateSerializedPropertyData( array $properties ) {
		foreach ( $properties as $propertyData ) {
			$blobId = $propertyData['id'];
			$blobData = json_decode( $propertyData['data'], true );
			$oldData = $blobData;

			$this->updateFormDataTypeId( $blobData );
			$this->updateSenseDataTypeId( $blobData );

			if ( $blobData !== $oldData ) {
				$newData = json_encode( $blobData );

				$this->db->update(
					'text',
					[ 'old_text' => $newData, ],
					[ 'old_id' => $blobId ],
					__METHOD__
				);

				$this->reporter->reportMessage(
					'Updated: ' . $propertyData['propertyId'] . ' (rev: ' . $propertyData['revisionId'] . ')'
				);
			}
		}
	}

	/**
	 * Changes property datatype from wikibase-lexeme-form to wikibase-form
	 */
	private function updateFormDataTypeId( array &$property ) {
		if ( $property['datatype'] !== 'wikibase-lexeme-form' ) {
			return;
		}

		$property['datatype'] = 'wikibase-form';
		$this->updatePropertyInfoWithDatatype( $property['id'], 'wikibase-form' );
	}

	private function updateSenseDataTypeId( array &$property ) {
		if ( $property['datatype'] !== 'wikibase-lexeme-sense' ) {
			return;
		}

		$property['datatype'] = 'wikibase-sense';
		$this->updatePropertyInfoWithDatatype( $property['id'], 'wikibase-sense' );
	}

	private function updatePropertyInfoWithDatatype( $id, $datatype ) {
		$this->db->update(
			'wb_property_info',
			[
				'pi_type' => $datatype,
				'pi_info' => json_encode( [ 'type' => $datatype ] ),
			],
			[ 'pi_property_id' => (int)ltrim( $id, 'P' ) ]
		);
	}

}
