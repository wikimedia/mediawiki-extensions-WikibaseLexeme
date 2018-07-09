<?php

namespace Wikibase\Lexeme\DataModel;

use Wikibase\DataModel\Entity\EntityId;
use Wikimedia\Assert\Assert;

/**
 * Immutable ID of a Lexeme's sense in the lexiographical data model.
 *
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLexeme/Data_Model#Sense
 *
 * @license GPL-2.0-or-later
 */
class SenseId extends EntityId {

	const PATTERN = '/^L[1-9]\d*-S[1-9]\d*\z/';

	/**
	 * @param string $serialization
	 */
	public function __construct( $serialization ) {
		parent::__construct( $serialization );

		Assert::parameter(
			preg_match( self::PATTERN, $this->localPart ),
			'$serialization',
			'Sense ID must match "' . self::PATTERN . '", given: ' . $this->localPart
		);
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return Sense::ENTITY_TYPE;
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
