<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLemmaEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLemmaRemove;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * Deserializer for lemma change request data.
 *
 * @see docs/change-op-serialization.wiki for a description of the serialization format.
 *
 * @license GPL-2.0-or-later
 */
class LemmaChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * @var \Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator
	 */
	private $lemmaTermValidator;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var LexemeTermSerializationValidator
	 */
	private $termSerializationValidator;

	private const LEMMAS_PARAM = 'lemmas';

	public function __construct(
		LexemeTermSerializationValidator $termChangeOpSerializationValidator,
		LemmaTermValidator $lemmaTermValidator,
		StringNormalizer $stringNormalizer
	) {
		$this->termSerializationValidator = $termChangeOpSerializationValidator;
		$this->lemmaTermValidator = $lemmaTermValidator;
		$this->stringNormalizer = $stringNormalizer;
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
		$this->assertIsArray( $changeRequest[self::LEMMAS_PARAM] );

		$changeOps = new ChangeOps();

		$validationContext = ValidationContext::create( self::LEMMAS_PARAM );
		foreach ( $changeRequest[self::LEMMAS_PARAM] as $languageCode => $serialization ) {
			$languageContext = $validationContext->at( $languageCode );

			$this->termSerializationValidator->validateStructure(
				$serialization,
				$languageContext
			);

			$lemmaTerm = array_key_exists( 'remove', $serialization ) ? '' :
				$this->stringNormalizer->trimToNFC( $serialization['value'] );

			if ( $lemmaTerm === '' ) {
				$changeOps->add( new ChangeOpLemmaRemove( $serialization['language'] ) );
				continue;
			}

			$this->termSerializationValidator->validateLanguage(
				$languageCode,
				$serialization,
				$languageContext
			);

			// TODO: maybe move creating ChangeOpLemmaEdit instance to some kind of factory?
			$changeOps->add( new ChangeOpLemmaEdit(
				$serialization['language'],
				$lemmaTerm,
				$this->lemmaTermValidator
			) );
		}

		return $changeOps;
	}

	private function assertIsArray( $lemmaSerialization ) {
		if ( !is_array( $lemmaSerialization ) ) {
			throw new ChangeOpDeserializationException(
				'List of lemmas must be an array', 'not-recognized-array'
			);
		}
	}

}
