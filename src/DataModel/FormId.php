<?php

namespace Wikibase\Lexeme\DataModel;

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
		if ( !preg_match( '/^F\d+$/', $serialization ) ) {
			throw new \InvalidArgumentException(
				"Form ID should have format `F\d+`. Given: $serialization"
			);
		}
		$this->serialization = $serialization;
	}

	/**
	 * @return string
	 */
	public function getSerialization() {
		return $this->serialization;
	}

}
