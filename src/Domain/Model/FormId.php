<?php

namespace Wikibase\Lexeme\Domain\Model;

use Wikimedia\Assert\Assert;

/**
 * Immutable ID of a Lexeme' form in the lexicographical data model.
 *
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLexeme/Data_Model#Form
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class FormId extends LexemeSubEntityId {

	public const PATTERN = '/^L[1-9]\d*-F[1-9]\d*\z/';

	/**
	 * @param string $serialization
	 */
	public function __construct( $serialization ) {
		parent::__construct( $serialization );

		list( , , $id ) = self::splitSerialization( $this->localPart );
		Assert::parameter(
			preg_match( self::PATTERN, $id ),
			'$serialization',
			'Form ID must match "' . self::PATTERN . '", given: ' . $id
		);
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return Form::ENTITY_TYPE;
	}

}
