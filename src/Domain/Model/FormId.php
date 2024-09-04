<?php

declare( strict_types = 1 );

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

	public function __construct( string $serialization ) {
		parent::__construct( $serialization );

		Assert::parameter(
			preg_match( self::PATTERN, $serialization ),
			'$serialization',
			'Form ID must match "' . self::PATTERN . '", given: ' . $serialization
		);
	}

	public function getEntityType(): string {
		return Form::ENTITY_TYPE;
	}

}
