<?php

namespace Wikibase\Lexeme\DataModel;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LexemeForm {

	/**
	 * @var string
	 */
	private $representation;

	/**
	 * @param string $representation
	 */
	public function __construct( $representation ) {
		$this->representation = $representation;
	}

	/**
	 * @return string
	 */
	public function getRepresentation() {
		return $this->representation;
	}

}
