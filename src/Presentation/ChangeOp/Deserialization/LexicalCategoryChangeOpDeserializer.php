<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use InvalidArgumentException;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLexicalCategory;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * Deserializer for lexical category change request data.
 *
 * @see docs/change-op-serialization.wiki for a description of the serialization format.
 *
 * @license GPL-2.0-or-later
 */
class LexicalCategoryChangeOpDeserializer implements ChangeOpDeserializer {

	private $lexicalCategoryValidator;
	private $stringNormalizer;

	public function __construct(
		ValueValidator $lexicalCategoryValidator,
		StringNormalizer $stringNormalizer
	) {
		$this->lexicalCategoryValidator = $lexicalCategoryValidator;
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

		return new ChangeOpLexicalCategory(
			$this->validateItemId( $value ),
			$this->lexicalCategoryValidator
		);
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
