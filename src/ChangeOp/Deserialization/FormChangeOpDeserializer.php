<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\ChangeOp\AddFormToLexemeChangeOp;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * Deserialize a creation request of a single form on a lexeme
 *
 * @see docs/change-op-serialization.wiki for a description of the serialization format.
 *
 * @license GPL-2.0-or-later
 */
class FormChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * In 'data' when creating 'new' => 'form' through wbeditentity
	 */
	const PARAM_LEXEME_ID = 'lexemeId';

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var EditFormChangeOpDeserializer
	 */
	private $editFormChangeOpDeserializer;

	/**
	 * @var ValidationContext
	 */
	private $validationContext;

	public function __construct(
		EntityLookup $entityLookup,
		EditFormChangeOpDeserializer $editFormChangeOpDeserializer
	) {
		$this->entityLookup = $entityLookup;
		$this->editFormChangeOpDeserializer = $editFormChangeOpDeserializer;
	}

	public function setContext( ValidationContext $context ) {
		$this->validationContext = $context;
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
		$this->editFormChangeOpDeserializer->setContext( $this->validationContext );
		$editFormChangeOp = $this->editFormChangeOpDeserializer->createEntityChangeOp( $changeRequest );

		// TODO: move to dedicated deserializer
		if ( array_key_exists( self::PARAM_LEXEME_ID, $changeRequest ) ) {
			/** @var Lexeme $lexeme */
			$lexeme = $this->entityLookup->getEntity(
				new LexemeId( $changeRequest[self::PARAM_LEXEME_ID] )
			);
			// TODO Use ChangeOp that sets summary
			return new ChangeOps( [
				new AddFormToLexemeChangeOp( $lexeme ),
				$editFormChangeOp
			] );
		}

		return $editFormChangeOp;
	}

}
