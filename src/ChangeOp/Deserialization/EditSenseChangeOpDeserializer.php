<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\ChangeOp\ChangeOpSenseEdit;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * Deserialize a change request on a single sense
 *
 * @license GPL-2.0-or-later
 */
class EditSenseChangeOpDeserializer implements ChangeOpDeserializer {

	const PARAM_GLOSSES = 'glosses';

	/**
	 * @var GlossesChangeOpDeserializer
	 */
	private $glossesChangeOpDeserializer;

	/**
	 * @var ValidationContext
	 */
	private $validationContext;

	public function __construct(
		GlossesChangeOpDeserializer $glossesChangeOpDeserializer
	) {
		$this->glossesChangeOpDeserializer = $glossesChangeOpDeserializer;
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

		return new ChangeOpSenseEdit( $changeOps );
	}

}
