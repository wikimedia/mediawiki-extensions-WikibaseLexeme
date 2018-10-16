<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use Wikibase\Lexeme\MediaWiki\Api\Error\InvalidFormClaims;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\ChangeOp\ChangeOpGrammaticalFeatures;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;

/**
 * Deserialize a change request on a single form
 *
 * @license GPL-2.0-or-later
 */
class EditFormChangeOpDeserializer implements ChangeOpDeserializer {

	const PARAM_REPRESENTATIONS = 'representations';

	const PARAM_GRAMM_FEAT = 'grammaticalFeatures';

	const PARAM_STATEMENTS = 'claims';

	/**
	 * @var RepresentationsChangeOpDeserializer
	 */
	private $representationsChangeOpDeserializer;

	/**
	 * @var ItemIdListDeserializer
	 */
	private $itemIdListDeserializer;

	/**
	 * @var ValidationContext
	 */
	private $validationContext;

	/**
	 * @var ClaimsChangeOpDeserializer
	 */
	private $statementsChangeOpDeserializer;

	public function __construct(
		RepresentationsChangeOpDeserializer $representationsChangeOpDeserializer,
		ItemIdListDeserializer $itemIdListDeserializer,
		ClaimsChangeOpDeserializer $statementsChangeOpDeserializer
	) {
		$this->representationsChangeOpDeserializer = $representationsChangeOpDeserializer;
		$this->itemIdListDeserializer = $itemIdListDeserializer;
		$this->statementsChangeOpDeserializer = $statementsChangeOpDeserializer;
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
		$changeOps = [];

		if ( array_key_exists( self::PARAM_REPRESENTATIONS, $changeRequest ) ) {
			$representations = $changeRequest[self::PARAM_REPRESENTATIONS];

			$representationsContext = $this->validationContext->at( self::PARAM_REPRESENTATIONS );

			if ( !is_array( $representations ) ) {
				$representationsContext->addViolation(
					new JsonFieldHasWrongType( 'array', gettype( $representations ) )
				);
			} else {
				$this->representationsChangeOpDeserializer->setContext( $representationsContext );
				$changeOps[] =
					$this->representationsChangeOpDeserializer->createEntityChangeOp( $representations );
			}
		}
		if ( array_key_exists( self::PARAM_GRAMM_FEAT, $changeRequest ) ) {
			$grammaticalFeatures = $changeRequest[self::PARAM_GRAMM_FEAT];

			$grammaticalFeatureContext = $this->validationContext->at( self::PARAM_GRAMM_FEAT );

			if ( !is_array( $grammaticalFeatures ) ) {
				$grammaticalFeatureContext->addViolation(
					new JsonFieldHasWrongType( 'array', gettype( $grammaticalFeatures ) )
				);
			} else {
				$changeOps[] = new ChangeOpGrammaticalFeatures(
					$this->itemIdListDeserializer->deserialize( $grammaticalFeatures, $grammaticalFeatureContext )
				);
			}
		}

		if ( array_key_exists( self::PARAM_STATEMENTS, $changeRequest ) ) {
			$statementsContext = $this->validationContext->at( self::PARAM_STATEMENTS );
			$statementsRequest = $changeRequest[self::PARAM_STATEMENTS];

			if ( !is_array( $statementsRequest ) ) {
				$statementsContext->addViolation(
					new JsonFieldHasWrongType( 'array', gettype( $statementsRequest ) )
				);
			} else {
				try {
					$changeOps[] = $this->statementsChangeOpDeserializer->createEntityChangeOp( $changeRequest );
				} catch ( ChangeOpDeserializationException $exception ) {
					$statementsContext->addViolation( new InvalidFormClaims() );
				}
			}
		}

		return new ChangeOpFormEdit( $changeOps );
	}

}
