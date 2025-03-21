<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model;

use InvalidArgumentException;
use LogicException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\SerializableEntityId;

/**
 * An entity ID of a sub-entity of a {@link Lexeme},
 * which contains the {@link LexemeId} of the parent lexeme
 * and exposes it via {@link getLexemeId()}.
 *
 * @license GPL-2.0-or-later
 */
abstract class LexemeSubEntityId extends SerializableEntityId {

	public const SUBENTITY_ID_SEPARATOR = '-';

	public function getSerialization(): string {
		return $this->serialization;
	}

	public function __serialize(): array {
		return [ 'serialization' => $this->serialization ];
	}

	public function __unserialize( array $data ): void {
		$this->__construct( $data['serialization'] ?? '' );
		if ( $this->serialization !== $data['serialization'] ) {
			throw new InvalidArgumentException( '$data contained invalid serialization' );
		}
	}

	public function getLexemeId(): LexemeId {
		return new LexemeId( $this->extractLexemeIdAndSubEntityId()[0] );
	}

	/**
	 * Returns the sub-entity id suffix, e.g. 'F1' for L1-F1, or 'S1' for L1-S1.
	 * Returns empty string for dummy ids and the like.
	 */
	public function getIdSuffix(): string {
		if ( $this->serialization !== null ) {
			return $this->extractLexemeIdAndSubEntityId()[1];
		}

		return '';
	}

	/**
	 * Format a serialization of a sub entity id, e.g. 'L1-F3'
	 *
	 * @param EntityId $containerEntityId Id of the entity in which the sub entity resides, e.g. L1
	 * @param string $idPrefix The prefix of the sub entity, e.g. 'F'
	 * @param int $id The id of the sub entity, e.g. '3'
	 *
	 * @return string
	 */
	public static function formatSerialization(
		EntityId $containerEntityId,
		string $idPrefix,
		int $id
	): string {
		return $containerEntityId->getSerialization() .
			self::SUBENTITY_ID_SEPARATOR .
			$idPrefix . $id;
	}

	/**
	 * This method should not be used for code that is expected to work with dummy ids.
	 *
	 * @return string[] two strings containing the lexeme id serialization and the sub-entity suffix,
	 *                  e.g. ['L1', 'F1'] for form id L1-F1.
	 */
	private function extractLexemeIdAndSubEntityId(): array {
		$parts = explode( self::SUBENTITY_ID_SEPARATOR, $this->serialization, 2 );

		if ( count( $parts ) !== 2 ) {
			throw new LogicException( 'Malformed sub-entity id' );
		}

		return $parts;
	}

}
