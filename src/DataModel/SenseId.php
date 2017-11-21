<?php

namespace Wikibase\Lexeme\DataModel;

/**
 * Immutable ID of a Lexeme's sense in the lexiographical data model.
 *
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLexeme/Data_Model#Sense
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
