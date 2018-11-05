<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveFormRepresentation;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentation;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentationList;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class RepresentationsChangeOpDeserializer implements ChangeOpDeserializer {

	const PARAM_LANGUAGE = 'language';

	/**
	 * @var TermDeserializer
	 */
	private $representationDeserializer;

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
		LexemeTermSerializationValidator $validator
	) {
		$this->representationDeserializer = $representationDeserializer;
		$this->termSerializationValidator = $validator;
	}

	public function setContext( ValidationContext $context ) {
		$this->validationContext = $context;
	}

	public function createEntityChangeOp( array $representations ) {
		$changeOps = [];

		foreach ( $representations as $language => $representation ) {
			$languageContext = $this->validationContext->at( $language );
			$this->termSerializationValidator->validate( $language, $representation, $languageContext );

			if ( array_key_exists( 'remove', $representation ) ) {
				$changeOps[] = new ChangeOpRemoveFormRepresentation( $representation[self::PARAM_LANGUAGE] );
			} else {
				$changeOps[] = new ChangeOpRepresentation(
					$this->representationDeserializer->deserialize( $representation )
				);
			}
		}

		return new ChangeOpRepresentationList( $changeOps );
	}

}
