<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseEdit;
use Wikibase\Lexeme\MediaWiki\Api\Error\InvalidSenseClaims;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;

/**
 * Deserialize a change request on a single sense
 *
 * @license GPL-2.0-or-later
 */
class EditSenseChangeOpDeserializer implements ChangeOpDeserializer {

	private const PARAM_GLOSSES = 'glosses';

	private const PARAM_STATEMENTS = 'claims';

	/**
	 * @var GlossesChangeOpDeserializer
	 */
	private $glossesChangeOpDeserializer;

	/**
	 * @var ValidationContext
	 */
	private $validationContext;

	/**
	 * @var ClaimsChangeOpDeserializer
	 */
	private $statementsChangeOpDeserializer;

	public function __construct(
		GlossesChangeOpDeserializer $glossesChangeOpDeserializer,
		ClaimsChangeOpDeserializer $statementsChangeOpDeserializer
	) {
		$this->glossesChangeOpDeserializer = $glossesChangeOpDeserializer;
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

		if ( array_key_exists( self::PARAM_GLOSSES, $changeRequest ) ) {
			$glosses = $changeRequest[self::PARAM_GLOSSES];

			$glossesContext = $this->validationContext->at( self::PARAM_GLOSSES );

			if ( !is_array( $glosses ) ) {
				$glossesContext->addViolation(
					new JsonFieldHasWrongType( 'array', gettype( $glosses ) )
				);
			} else {
				$this->glossesChangeOpDeserializer->setContext( $glossesContext );
				$changeOps[] =
					$this->glossesChangeOpDeserializer->createEntityChangeOp( $glosses );
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
					$statementsContext->addViolation( new InvalidSenseClaims() );
				}
			}
		}

		return new ChangeOpSenseEdit( $changeOps );
	}

}
