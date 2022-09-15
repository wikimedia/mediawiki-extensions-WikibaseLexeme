<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveFormRepresentation;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentation;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentationList;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class RepresentationsChangeOpDeserializer implements ChangeOpDeserializer {

	private const PARAM_LANGUAGE = 'language';
	private const PARAM_VALUE = 'value';

	/**
	 * @var TermDeserializer
	 */
	private $representationDeserializer;

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
		TermDeserializer $representationDeserializer,
		StringNormalizer $stringNormalizer,
		LexemeTermSerializationValidator $validator
	) {
		$this->representationDeserializer = $representationDeserializer;
		$this->stringNormalizer = $stringNormalizer;
		$this->termSerializationValidator = $validator;
	}

	public function setContext( ValidationContext $context ) {
		$this->validationContext = $context;
	}

	/**
	 * @param array[] $representations
	 * @return ChangeOpRepresentationList
	 */
	public function createEntityChangeOp( array $representations ) {
		$changeOps = [];

		foreach ( $representations as $language => $representation ) {
			$languageContext = $this->validationContext->at( $language );
			$this->termSerializationValidator->validateStructure( $representation, $languageContext );

			if ( array_key_exists( 'remove', $representation ) ) {
				$changeOps[] = new ChangeOpRemoveFormRepresentation( $representation[self::PARAM_LANGUAGE] );
			} else {
				$this->termSerializationValidator->validateLanguage(
					$language, $representation, $languageContext );
				$trimmedRepresentation = [
					self::PARAM_LANGUAGE => $representation[self::PARAM_LANGUAGE],
					self::PARAM_VALUE => $this->stringNormalizer->trimToNFC( $representation[self::PARAM_VALUE] )
				];

				$changeOps[] = new ChangeOpRepresentation(
					$this->representationDeserializer->deserialize( $trimmedRepresentation )
				);
			}
		}

		return new ChangeOpRepresentationList( $changeOps );
	}

}
