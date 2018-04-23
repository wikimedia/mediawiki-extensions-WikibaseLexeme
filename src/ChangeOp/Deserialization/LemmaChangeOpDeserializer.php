<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Lexeme\ChangeOp\ChangeOpLemma;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
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
	 * @var LexemeValidatorFactory
	 */
	private $lexemeValidatorFactory;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var TermSerializationValidator
	 */
	private $termSerializationValidator;

	public function __construct(
		TermSerializationValidator $termChangeOpSerializationValidator,
		LexemeValidatorFactory $lexemeValidatorFactory,
		StringNormalizer $stringNormalizer
	) {
		$this->termSerializationValidator = $termChangeOpSerializationValidator;
		$this->lexemeValidatorFactory = $lexemeValidatorFactory;
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
		$this->assertIsArray( $changeRequest['lemmas'] );

		$changeOps = new ChangeOps();

		foreach ( $changeRequest['lemmas'] as $languageCode => $serialization ) {
			$this->termSerializationValidator->validate(
				$serialization,
				$languageCode
			);

			$lemmaTerm = array_key_exists( 'remove', $serialization ) ? '' :
				$this->stringNormalizer->cleanupToNFC( $serialization['value'] );

			if ( $lemmaTerm === '' ) {
				$changeOps->add( new ChangeOpLemma(
					$serialization['language'],
					null,
					$this->lexemeValidatorFactory
				) );
				continue;
			}

			// TODO: maybe move creating ChangeOpLemma instance to some kind of factory?
			$changeOps->add( new ChangeOpLemma(
				$serialization['language'],
				$lemmaTerm,
				$this->lexemeValidatorFactory
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
