<?php

namespace Wikibase\Lexeme\DataModel;

use Wikibase\DataModel\Entity\EntityId;

/**
 * An entity ID of a sub-entity of a {@link Lexeme},
 * which contains the {@link LexemeId} of the parent lexeme
 * and exposes it via {@link getLexemeId()}.
 *
 * @license GPL-2.0-or-later
 */
abstract class LexemeSubEntityId extends EntityId {

	/**
	 * @return string
	 */
	public function getSerialization() {
		return $this->serialization;
	}

	/**
	 * @return string
	 */
	public function serialize() {
		return $this->serialization;
	}

	/**
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		$this->serialization = $serialized;
		list( $this->repositoryName, $this->localPart ) = self::extractRepositoryNameAndLocalPart(
			$serialized
		);
	}

	/**
	 * @return LexemeId
	 */
	public function getLexemeId() {
		return new LexemeId( explode( '-', $this->localPart, 2 )[0] );
	}

}
