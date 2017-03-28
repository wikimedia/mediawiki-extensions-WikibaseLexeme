<?php

namespace Wikibase\Lexeme\DataModel;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LexemeFormId {

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
