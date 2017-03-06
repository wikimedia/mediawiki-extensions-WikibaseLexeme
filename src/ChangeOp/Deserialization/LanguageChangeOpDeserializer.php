<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\ChangeOp\ChangeOpLanguage;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\StringNormalizer;

/**
 * Deserializer for language change request data.
 *
 * @see docs/change-op-serialization.wiki for a description of the serialization format.
 *
 * @license GPL-2.0+
 */
class LanguageChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * @var LexemeValidatorFactory
	 */
	private $lexemeValidatorFactory;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	public function __construct(
		LexemeValidatorFactory $lexemeValidatorFactory,
		StringNormalizer $stringNormalizer
	) {
		$this->lexemeValidatorFactory = $lexemeValidatorFactory;
		$this->stringNormalizer = $stringNormalizer;
	}

	/**
	 * @see ChangeOpDeserializer::createEntityChangeOp
	 *
	 * @param array $changeRequest
	 *
	 * @throws ChangeOpDeserializationException
	 * @return ChangeOp
	 */
	public function createEntityChangeOp( array $changeRequest ) {
		if ( !array_key_exists( 'language', $changeRequest )
			|| ( !is_string( $changeRequest['language'] ) && $changeRequest['language'] !== null )
		) {
			throw new ChangeOpDeserializationException(
				'language must be a string or null',
				'invalid-language'
			);
		}

		$value = $changeRequest['language'];
		$value = $value === null ? '' : $this->stringNormalizer->cleanupToNFC( $value );

		if ( $value === '' ) {
			return new ChangeOpLanguage( null, $this->lexemeValidatorFactory );
		}

		$itemId = $this->validateItemId( $value );
		// TODO: maybe move creating ChangeOpLanguage instance to some kind of factory?
		return new ChangeOpLanguage( $itemId, $this->lexemeValidatorFactory );
	}

	/**
	 * @param string $idSerialization
	 *
	 * @return ItemId
	 * @throws ChangeOpDeserializationException
	 */
	private function validateItemId( $idSerialization ) {
		try {
			return new ItemId( $idSerialization );
		} catch ( InvalidArgumentException $e ) {
			throw new ChangeOpDeserializationException(
				'Item id can not be parsed',
				'invalid-item-id'
			);
		}
	}

}
