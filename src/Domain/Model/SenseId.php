<?php

namespace Wikibase\Lexeme\Domain\Model;

use Wikimedia\Assert\Assert;

/**
 * Immutable ID of a Lexeme's sense in the lexicographical data model.
 *
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLexeme/Data_Model#Sense
 *
 * @license GPL-2.0-or-later
 */
class SenseId extends LexemeSubEntityId {

	public const PATTERN = '/^L[1-9]\d*-S[1-9]\d*\z/';

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

}
