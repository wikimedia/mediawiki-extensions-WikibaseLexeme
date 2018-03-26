<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use Wikibase\Lexeme\Api\ApiRequestValidator;
use Wikibase\Lexeme\Api\Constraint\RemoveFormConstraint;
use Wikibase\Lexeme\ChangeOp\ChangeOpRemoveForm;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * Deserializer for form change request data.
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
		$this->assertChangeRequest( $changeRequest );

		$changeOps = new ChangeOps();

		foreach ( $changeRequest['forms'] as $serializedForm ) {
			if ( array_key_exists( 'remove', $serializedForm ) ) {
				$changeOps->add( new ChangeOpRemoveForm( new FormId( $serializedForm['id'] ) ) );
			}
		}

		return $changeOps;
	}

	/**
	 * @throws ChangeOpDeserializationException
	 *
	 * @param array $changeRequest
	 */
	private function assertChangeRequest( array $changeRequest ) {
		$validator = new ApiRequestValidator();
		$violations = $validator->validate( $changeRequest, RemoveFormConstraint::many() );
		$validator->convertViolationsToException( $violations );
	}

}
