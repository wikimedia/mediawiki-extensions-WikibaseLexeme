<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\Lexeme\ChangeOp\ChangeOpGloss;
use Wikibase\Lexeme\ChangeOp\ChangeOpGlossList;
use Wikibase\Lexeme\ChangeOp\ChangeOpRemoveSenseGloss;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class GlossesChangeOpDeserializer implements ChangeOpDeserializer {

	const PARAM_LANGUAGE = 'language';

	/**
	 * @var TermDeserializer
	 */
	private $glossDeserializer;

	/**
	 * @var ValidationContext
	 */
	private $validationContext;

	/**
	 * @var LexemeTermSerializationValidator
	 */
	private $termSerializationValidator;

	public function __construct(
		TermDeserializer $glossDeserializer,
		LexemeTermSerializationValidator $validator
	) {
		$this->glossDeserializer = $glossDeserializer;
		$this->termSerializationValidator = $validator;
	}

	public function setContext( ValidationContext $context ) {
		$this->validationContext = $context;
	}

	public function createEntityChangeOp( array $glosses ) {
		$changeOps = [];

		foreach ( $glosses as $language => $gloss ) {
			$languageContext = $this->validationContext->at( $language );
			$this->termSerializationValidator->validate( $language, $gloss, $languageContext );

			if ( array_key_exists( 'remove', $gloss ) ) {
				$changeOps[] = new ChangeOpRemoveSenseGloss( $gloss[self::PARAM_LANGUAGE] );
			} else {
				$changeOps[] = new ChangeOpGloss(
					$this->glossDeserializer->deserialize( $gloss )
				);
			}
		}

		return new ChangeOpGlossList( $changeOps );
	}

}
