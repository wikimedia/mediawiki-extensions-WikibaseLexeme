<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use InvalidArgumentException;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\ChangeOp\ChangeOpLexicalCategory;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\StringNormalizer;

/**
 * Deserializer for lexical category change request data.
 *
 * @see docs/change-op-serialization.wiki for a description of the serialization format.
 *
 * @license GPL-2.0-or-later
 */
class LexicalCategoryChangeOpDeserializer implements ChangeOpDeserializer {

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
		if ( !array_key_exists( 'lexicalCategory', $changeRequest )
			|| !is_string( $changeRequest['lexicalCategory'] )
		) {
			throw new ChangeOpDeserializationException(
				'lexicalCategory must be a string',
				'invalid-lexical-category'
			);
		}

		$value = $this->stringNormalizer->cleanupToNFC( $changeRequest['lexicalCategory'] );

		$itemId = $this->validateItemId( $value );
		return new ChangeOpLexicalCategory( $itemId, $this->lexemeValidatorFactory );
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
