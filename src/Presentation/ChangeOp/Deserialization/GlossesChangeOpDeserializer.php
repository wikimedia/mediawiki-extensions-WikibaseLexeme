<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGloss;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGlossList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveSenseGloss;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\StringNormalizer;

/**
 * @license GPL-2.0-or-later
 */
class GlossesChangeOpDeserializer implements ChangeOpDeserializer {

	const PARAM_LANGUAGE = 'language';
	const PARAM_VALUE = 'value';

	/**
	 * @var TermDeserializer
	 */
	private $glossDeserializer;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

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
		StringNormalizer $stringNormalizer,
		LexemeTermSerializationValidator $validator
	) {
		$this->glossDeserializer = $glossDeserializer;
		$this->stringNormalizer = $stringNormalizer;
		$this->termSerializationValidator = $validator;
	}

	public function setContext( ValidationContext $context ) {
		$this->validationContext = $context;
	}

	/**
	 * @param array[] $glosses
	 * @return ChangeOpGlossList
	 */
	public function createEntityChangeOp( array $glosses ) {
		$changeOps = [];

		foreach ( $glosses as $language => $gloss ) {
			$languageContext = $this->validationContext->at( $language );
			$this->termSerializationValidator->validate( $language, $gloss, $languageContext );

			if ( array_key_exists( 'remove', $gloss ) ) {
				$changeOps[] = new ChangeOpRemoveSenseGloss( $gloss[self::PARAM_LANGUAGE] );
			} else {
				$trimmedGloss = [
					self::PARAM_LANGUAGE => $gloss[self::PARAM_LANGUAGE],
					self::PARAM_VALUE => $this->stringNormalizer->trimToNFC( $gloss[self::PARAM_VALUE] )
				];

				$changeOps[] = new ChangeOpGloss(
					$this->glossDeserializer->deserialize( $trimmedGloss )
				);
			}
		}

		return new ChangeOpGlossList( $changeOps );
	}

}
