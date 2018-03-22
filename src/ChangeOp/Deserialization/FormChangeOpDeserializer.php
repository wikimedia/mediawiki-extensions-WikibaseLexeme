<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use Wikibase\Lexeme\ChangeOp\ChangeOpRemoveForm;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * Deserializer for form change request data.
 *
 * TODO Check synergies with RemoveFormRequestParser
 *
 * @see docs/change-op-serialization.wiki for a description of the serialization format.
 *
 * @license GPL-2.0-or-later
 */
class FormChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * @see ChangeOpDeserializer::createEntityChangeOp
	 *
	 * @param array $changeRequest
	 *
	 * @throws ChangeOpDeserializationException
	 *
	 * @return ChangeOp
	 */
	public function createEntityChangeOp( array $changeRequest ) {
		$this->assertIsArray( $changeRequest['forms'] );

		$changeOps = new ChangeOps();

		foreach ( $changeRequest['forms'] as $serializedForm ) {
			if ( array_key_exists( 'remove', $serializedForm ) ) {
				$changeOps->add( $this->getRemoveChangeOp( $serializedForm ) );
			}
		}

		return $changeOps;
	}

	/**
	 * @param array $serializedForm
	 *
	 * @throws ChangeOpDeserializationException
	 *
	 * @return ChangeOp
	 */
	private function getRemoveChangeOp( array $serializedForm ) {
		$this->assertHasFormId( $serializedForm );
		$id = $serializedForm['id'];
		$this->assertFormId( $id );

		return new ChangeOpRemoveForm( new FormId( $id ) );
	}

	private function assertIsArray( $formsSerialization ) {
		if ( !is_array( $formsSerialization ) ) {
			throw new ChangeOpDeserializationException(
				'List of forms must be an array',
				'not-recognized-array'
			);
		}
	}

	private function assertHasFormId( array $serializedForm ) {
		if ( !array_key_exists( 'id', $serializedForm ) ) {
			throw new ChangeOpDeserializationException(
				'Form id must be passed',
				'form-id-missing' // TODO add to i18n
			);
		}
	}

	private function assertFormId( $id ) {
		if ( !is_string( $id ) ) {
			throw new ChangeOpDeserializationException(
				'Form id must be a string',
				'parameter-not-form-id' // TODO add to i18n
			);
		}
	}

}
