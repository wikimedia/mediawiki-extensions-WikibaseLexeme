<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use InvalidArgumentException;
use ValueValidators\StringValidator;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
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
class LanguageChangeOpDeserializer implements ChangeOpDeserializer{

	/**
	 * @var LexemeValidatorFactory
	 */
	private $lexemeValidatorFactory;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var StringValidator
	 */
	private $stringValidator;

	public function __construct(
		LexemeValidatorFactory $lexemeValidatorFactory,
		StringNormalizer $stringNormalizer,
		StringValidator $stringValidator
	) {
		$this->lexemeValidatorFactory = $lexemeValidatorFactory;
		$this->stringNormalizer = $stringNormalizer;
		$this->stringValidator = $stringValidator;
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
		$changeOps = new ChangeOps();

		$result = $this->stringValidator->validate( $changeRequest['language'] );
		if ( $result->isValid() !== true ) {
			throw new ChangeOpDeserializationException(
				'language needs to be string',
				'invalid-language'
			);
		}
		$languageSerialization = $this->stringNormalizer->cleanupToNFC( $changeRequest['language'] );

		if ( $languageSerialization === '' ) {
			$changeOps->add( new ChangeOpLanguage(
				null,
				$this->lexemeValidatorFactory
			) );
			return $changeOps;
		}

		$itemId = $this->validateItemId( $languageSerialization );
		// TODO: maybe move creating ChangeOpLanguage instance to some kind of factory?
		$changeOps->add( new ChangeOpLanguage(
			$itemId,
			$this->lexemeValidatorFactory
		) );

		return $changeOps;
	}

	/**
	 * @param string $idSerialization
	 * @return ItemId
	 * @throws ChangeOpDeserializationException
	 */
	private function validateItemId( $idSerialization ) {
		try {
			$itemId = new ItemId( $idSerialization );
		} catch ( InvalidArgumentException $e ) {
			throw new ChangeOpDeserializationException(
				'Item id can not be parsed',
				'invalid-item-id'
			);
		}

		return $itemId;
	}

}
