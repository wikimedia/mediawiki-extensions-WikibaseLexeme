<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOp;
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
 * @license GPL-2.0+
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
	 *
	 * @return ChangeOp
	 */
	public function createEntityChangeOp( array $changeRequest ) {

		$lexicalCategorySerialization = $changeRequest['lexicalCategory'];
		if ( !isset( $lexicalCategorySerialization )
			|| ( !is_string( $lexicalCategorySerialization ) && $lexicalCategorySerialization !== null )
		) {
			throw new ChangeOpDeserializationException(
				'lexicalCategory must be string or null',
				'invalid-lexical-category'
			);
		}

		$lexicalCategorySerialization = $this->stringNormalizer->cleanupToNFC(
			$changeRequest['lexicalCategory'] );

		if ( $lexicalCategorySerialization === '' ) {
			return new ChangeOpLexicalCategory(
				null,
				$this->lexemeValidatorFactory
			);
		}

		$itemId = $this->validateItemId( $lexicalCategorySerialization );
		return new ChangeOpLexicalCategory( $itemId, $this->lexemeValidatorFactory );
	}

	/**
	 * @param string $idSerialization
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
