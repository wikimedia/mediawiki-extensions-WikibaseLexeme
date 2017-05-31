<?php

namespace Wikibase\Lexeme\DataModel;

/**
 * An ID of a sense of a lexeme.
 *
 * @license GPL-2.0+
 */
class SenseId {

	/**
	 * @var string
	 */
	private $serialization;

	/**
	 * @param string $serialization
	 */
	public function __construct( $serialization ) {
		$this->serialization = $serialization;
	}

	/**
	 * @return string
	 */
	public function getSerialization() {
		return $this->serialization;
	}

}
