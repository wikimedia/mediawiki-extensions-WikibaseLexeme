<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormAdd;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveForm;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpsFormsEdit;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldIsRequired;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * Deserialize change requests on multiple forms
 *
 * @see docs/change-op-serialization.wiki for a description of the serialization format.
 *
 * @license GPL-2.0-or-later
 */
class FormListChangeOpDeserializer implements ChangeOpDeserializer {

	private const PARAM_FORM_ID = 'id';

	/**
	 * @var FormChangeOpDeserializer
	 */
	private $formChangeOpDeserializer;

	/**
	 * @var FormIdDeserializer
	 */
	private $formIdDeserializer;

	/**
	 * @var ValidationContext
	 */
	private $validationContext;

	public function __construct(
		FormIdDeserializer $formIdDeserializer,
		FormChangeOpDeserializer $formChangeOpDeserializer
	) {
		$this->formChangeOpDeserializer = $formChangeOpDeserializer;
		$this->formIdDeserializer = $formIdDeserializer;
	}

	public function setContext( ValidationContext $context ) {
		$this->validationContext = $context;
	}

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
		$lexemeChangeOps = new ChangeOps();
		$changeOpsForForm = [];

		if ( !is_array( $changeRequest['forms'] ) ) {
			$this->validationContext->addViolation(
				new JsonFieldHasWrongType( 'array', gettype( $changeRequest['forms'] ) )
			);
		}

		foreach ( $changeRequest['forms'] as $index => $serializedForm ) {
			$formValidationContext = $this->validationContext->at( $index );
			$this->formChangeOpDeserializer->setContext( $formValidationContext );

			if ( !is_array( $serializedForm ) ) {
				$formValidationContext->addViolation(
					new JsonFieldHasWrongType( 'array', gettype( $serializedForm ) )
				);
			}

			if ( array_key_exists( 'remove', $serializedForm ) ) {
				if ( !array_key_exists( self::PARAM_FORM_ID, $serializedForm ) ) {
					$formValidationContext->addViolation(
						new JsonFieldIsRequired( self::PARAM_FORM_ID )
					);
				}

				$formId = $this->formIdDeserializer->deserialize(
					$serializedForm[self::PARAM_FORM_ID],
					$formValidationContext->at( self::PARAM_FORM_ID )
				);

				$lexemeChangeOps->add( new ChangeOpRemoveForm( $formId ) );
			} elseif ( array_key_exists( 'add', $serializedForm ) ) {
				$lexemeChangeOps->add(
					new ChangeOpFormAdd(
						$this->formChangeOpDeserializer->createEntityChangeOp( $serializedForm )
					)
				);
			} elseif ( array_key_exists( self::PARAM_FORM_ID, $serializedForm ) ) {
				$changeOpsForForm[$serializedForm[self::PARAM_FORM_ID]] =
					$this->formChangeOpDeserializer->createEntityChangeOp( $serializedForm );
			}
		}

		return new ChangeOps( [ $lexemeChangeOps, new ChangeOpsFormsEdit( $changeOpsForForm ) ] );
	}

}
