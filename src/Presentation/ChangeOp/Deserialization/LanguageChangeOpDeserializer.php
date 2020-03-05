<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use InvalidArgumentException;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLanguage;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * Deserializer for language change request data.
 *
 * @see docs/change-op-serialization.wiki for a description of the serialization format.
 *
 * @license GPL-2.0-or-later
 */
class LanguageChangeOpDeserializer implements ChangeOpDeserializer {

	private $languageValidator;
	private $stringNormalizer;

	public function __construct(
		ValueValidator $languageValidator,
		StringNormalizer $stringNormalizer
	) {
		$this->languageValidator = $languageValidator;
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
			|| !is_string( $changeRequest['language'] )
		) {
			throw new ChangeOpDeserializationException(
				'language must be a string',
				'invalid-language'
			);
		}

		$value = $this->stringNormalizer->cleanupToNFC( $changeRequest['language'] );

		$itemId = $this->validateItemId( $value );
		// TODO: maybe move creating ChangeOpLanguage instance to some kind of factory?
		return new ChangeOpLanguage( $itemId, $this->languageValidator );
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
