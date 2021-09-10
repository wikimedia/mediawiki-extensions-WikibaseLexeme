<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\DataAccess\ChangeOp\AddFormToLexemeChangeOp;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeNotFound;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotLexemeId;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\NullChangeOp;

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
	private const PARAM_LEXEME_ID = 'lexemeId';

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var EditFormChangeOpDeserializer
	 */
	private $editFormChangeOpDeserializer;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var ValidationContext
	 */
	private $validationContext;

	public function __construct(
		EntityLookup $entityLookup,
		EntityIdParser $idParser,
		EditFormChangeOpDeserializer $editFormChangeOpDeserializer
	) {
		$this->entityLookup = $entityLookup;
		$this->entityIdParser = $idParser;
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
			$lexemeId = $this->getLexemeId( $changeRequest[self::PARAM_LEXEME_ID] );
			$idContext = $this->validationContext->at( self::PARAM_LEXEME_ID );

			if ( $lexemeId === null ) {
				$idContext->addViolation(
					new ParameterIsNotLexemeId( $changeRequest[self::PARAM_LEXEME_ID ] )
				);
				return new NullChangeOp();
			}
			/** @var Lexeme $lexeme */
			$lexeme = $this->entityLookup->getEntity( $lexemeId );
			'@phan-var Lexeme $lexeme';
			if ( $lexeme === null ) {
				$idContext->addViolation( new LexemeNotFound( $lexemeId ) );
				return new NullChangeOp();
			}
			// TODO Use ChangeOp that sets summary
			return new AddFormToLexemeChangeOp(
				$lexeme,
				$editFormChangeOp
			);
		}

		return $editFormChangeOp;
	}

	/**
	 * @param string $id
	 * @return LexemeId|null
	 */
	private function getLexemeId( $id ) {
		try {
			$lexemeId = $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException $ex ) {
			return null;
		}

		if ( $lexemeId->getEntityType() !== Lexeme::ENTITY_TYPE ) {
			return null;
		}

		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $lexemeId;
	}

}
