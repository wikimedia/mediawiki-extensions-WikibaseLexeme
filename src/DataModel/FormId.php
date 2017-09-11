<?php

namespace Wikibase\Lexeme\DataModel;

use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class FormId {

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
			preg_match( '/^F[1-9]\d*\z/', $serialization ),
			'$serialization',
			'Form ID must match "F[1-9]\d*", given: ' . $serialization
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
