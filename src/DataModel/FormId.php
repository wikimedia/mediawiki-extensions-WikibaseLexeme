<?php

namespace Wikibase\Lexeme\DataModel;

use Wikimedia\Assert\Assert;

/**
 * Immutable ID of a Lexeme' form in the lexiographical data model.
 *
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLexeme/Data_Model#Form
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class FormId {

	const PATTERN = '/^L[1-9]\d*-F[1-9]\d*\z/';

	/**
	 * @var string
	 */
	private $serialization;

	/**
	 * @param string $serialization
	 */
	public function __construct( $serialization ) {
		Assert::parameterType( 'string', $serialization, '$serialization' );
		Assert::parameter(
			preg_match( self::PATTERN, $serialization ),
			'$serialization',
			'Form ID must match "' . self::PATTERN . '", given: ' . $serialization
		);

		$this->serialization = $serialization;
	}

	/**
	 * @return string
	 */
	public function getSerialization() {
		return $this->serialization;
	}

}
