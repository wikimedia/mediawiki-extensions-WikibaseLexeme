<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use ValueValidators\ValueValidator;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Lexeme\ChangeOp\ChangeOpLemma;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\StringNormalizer;

/**
 * Deserializer for lemma change request data.
 *
 * @see docs/change-op-serialization.wiki for a description of the serialization format.
 *
 * @license GPL-2.0-or-later
 */
class LemmaChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * @var ValueValidator
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

	const LEMMAS_PARAM = 'lemmas';

	public function __construct(
		LexemeTermSerializationValidator $termChangeOpSerializationValidator,
		ValueValidator $lemmaTermValidator,
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

			$this->termSerializationValidator->validate(
				$languageCode,
				$serialization,
				$languageContext
			);

			$lemmaTerm = array_key_exists( 'remove', $serialization ) ? '' :
				$this->stringNormalizer->cleanupToNFC( $serialization['value'] );

			if ( $lemmaTerm === '' ) {
				$changeOps->add( new ChangeOpLemma(
					$serialization['language'],
					null,
					$this->lemmaTermValidator
				) );
				continue;
			}

			// TODO: maybe move creating ChangeOpLemma instance to some kind of factory?
			$changeOps->add( new ChangeOpLemma(
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
