<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use ValueValidators\ValueValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGrammaticalFeatures;
use Wikibase\Lexeme\MediaWiki\Api\Error\InvalidFormClaims;
use Wikibase\Lexeme\MediaWiki\Api\Error\InvalidItemId;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType;
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

	private const PARAM_REPRESENTATIONS = 'representations';

	private const PARAM_GRAMM_FEAT = 'grammaticalFeatures';

	private const PARAM_STATEMENTS = 'claims';

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

	/**
	 * @var ValueValidator
	 */
	private $entityExistsValidator;

	public function __construct(
		RepresentationsChangeOpDeserializer $representationsChangeOpDeserializer,
		ItemIdListDeserializer $itemIdListDeserializer,
		ClaimsChangeOpDeserializer $statementsChangeOpDeserializer,
		ValueValidator $entityExistsValidator
	) {
		$this->representationsChangeOpDeserializer = $representationsChangeOpDeserializer;
		$this->itemIdListDeserializer = $itemIdListDeserializer;
		$this->statementsChangeOpDeserializer = $statementsChangeOpDeserializer;
		$this->entityExistsValidator = $entityExistsValidator;
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
				$itemIds = $this->itemIdListDeserializer->deserialize(
					$grammaticalFeatures,
					$grammaticalFeatureContext
				);

				foreach ( $itemIds as $itemId ) {
					if ( $this->entityExistsValidator->validate( $itemId )->getErrors() ) {
						$grammaticalFeatureContext->addViolation(
							new InvalidItemId( $itemId->getSerialization() )
						);
					}
				}

				$changeOps[] = new ChangeOpGrammaticalFeatures( $itemIds );
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
