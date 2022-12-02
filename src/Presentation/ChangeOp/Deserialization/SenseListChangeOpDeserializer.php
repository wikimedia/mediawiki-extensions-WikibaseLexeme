<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveSense;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseAdd;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpsSensesEdit;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldIsRequired;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * Deserialize change requests on multiple senses
 *
 * @see docs/change-op-serialization.wiki for a description of the serialization format.
 *
 * @license GPL-2.0-or-later
 */
class SenseListChangeOpDeserializer implements ChangeOpDeserializer {

	private const PARAM_SENSE_ID = 'id';

	/**
	 * @var SenseChangeOpDeserializer
	 */
	private $senseChangeOpDeserializer;

	/**
	 * @var SenseIdDeserializer
	 */
	private $senseIdDeserializer;

	/**
	 * @var ValidationContext
	 */
	private $validationContext;

	public function __construct(
		SenseIdDeserializer $senseIdDeserializer,
		SenseChangeOpDeserializer $senseChangeOpDeserializer
	) {
		$this->senseChangeOpDeserializer = $senseChangeOpDeserializer;
		$this->senseIdDeserializer = $senseIdDeserializer;
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
		$changeOpsForSense = [];

		if ( !is_array( $changeRequest['senses'] ) ) {
			$this->validationContext->addViolation(
				new JsonFieldHasWrongType( 'array', gettype( $changeRequest['senses'] ) )
			);
		}

		foreach ( $changeRequest['senses'] as $index => $serializedSense ) {
			$senseValidationContext = $this->validationContext->at( $index );
			$this->senseChangeOpDeserializer->setContext( $senseValidationContext );

			if ( !is_array( $serializedSense ) ) {
				$senseValidationContext->addViolation(
					new JsonFieldHasWrongType( 'array', gettype( $serializedSense ) )
				);
			}

			if ( array_key_exists( 'remove', $serializedSense ) ) {
				if ( !array_key_exists( self::PARAM_SENSE_ID, $serializedSense ) ) {
					$senseValidationContext->addViolation(
						new JsonFieldIsRequired( self::PARAM_SENSE_ID )
					);
				}

				$senseId = $this->senseIdDeserializer->deserialize(
					$serializedSense[self::PARAM_SENSE_ID],
					$senseValidationContext->at( self::PARAM_SENSE_ID )
				);

				$lexemeChangeOps->add( new ChangeOpRemoveSense( $senseId ) );
			} elseif ( array_key_exists( 'add', $serializedSense ) ) {
				$lexemeChangeOps->add( new ChangeOpSenseAdd(
					$this->senseChangeOpDeserializer->createEntityChangeOp( $serializedSense ),
					new GuidGenerator()
				) );
			} elseif ( array_key_exists( self::PARAM_SENSE_ID, $serializedSense ) ) {
				$changeOpsForSense[$serializedSense[self::PARAM_SENSE_ID]] =
					$this->senseChangeOpDeserializer->createEntityChangeOp( $serializedSense );
			}
		}

		return new ChangeOps( [ $lexemeChangeOps, new ChangeOpsSensesEdit( $changeOpsForSense ) ] );
	}

}
