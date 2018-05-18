<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\Lexeme\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\Api\Error\JsonFieldIsRequired;
use Wikibase\Lexeme\Api\Error\RepresentationLanguageCanNotBeEmpty;
use Wikibase\Lexeme\Api\Error\RepresentationLanguageInconsistent;
use Wikibase\Lexeme\ChangeOp\ChangeOpRemoveFormRepresentation;
use Wikibase\Lexeme\ChangeOp\ChangeOpRepresentation;
use Wikibase\Lexeme\ChangeOp\ChangeOpRepresentationList;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class RepresentationsChangeOpDeserializer implements ChangeOpDeserializer {

	const PARAM_LANGUAGE = 'language';

	/**
	 * @var TermDeserializer
	 */
	private $representationDeserializer;

	/**
	 * @var ValidationContext
	 */
	private $validationContext;

	public function __construct( TermDeserializer $representationDeserializer ) {
		$this->representationDeserializer = $representationDeserializer;
	}

	public function setContext( ValidationContext $context ) {
		$this->validationContext = $context;
	}

	public function createEntityChangeOp( array $representations ) {
		$changeOps = [];

		foreach ( $representations as $language => $representation ) {
			if ( empty( $language ) ) {
				$this->validationContext->addViolation(
					new RepresentationLanguageCanNotBeEmpty()
				);
				continue;
			}

			$languageContext = $this->validationContext->at( $language );

			if ( !array_key_exists( self::PARAM_LANGUAGE, $representation ) ) {
				$languageContext->addViolation(
					new JsonFieldIsRequired( self::PARAM_LANGUAGE )
				);
				continue;
			}

			if ( $language !== $representation[self::PARAM_LANGUAGE] ) {
				$languageContext->addViolation(
					new RepresentationLanguageInconsistent( $language, $representation[self::PARAM_LANGUAGE] )
				);
				continue;
			}

			if ( array_key_exists( 'remove', $representation ) ) {
				$changeOps[] = new ChangeOpRemoveFormRepresentation( $representation[self::PARAM_LANGUAGE] );
			} else {
				// TODO context-aware representationDeserializer
				try {
					$representation = $this->representationDeserializer->deserialize( $representation );
				} catch ( MissingAttributeException $exception ) {
					$languageContext->addViolation(
						new JsonFieldIsRequired( $exception->getAttributeName() )
					);
					continue;
				} catch ( InvalidAttributeException $exception ) {
					$languageContext->addViolation(
						new JsonFieldHasWrongType( 'string', gettype( $exception->getAttributeValue() ) )
					);
					continue;
				} catch ( DeserializationException $exception ) {
					// TODO patch vs full request (compare FormMustHaveAtLeastOneRepresentation)
					continue;
				}

				$changeOps[] = new ChangeOpRepresentation( $representation );
			}
		}

		return new ChangeOpRepresentationList( $changeOps );
	}

}
