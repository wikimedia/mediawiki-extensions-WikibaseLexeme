<?php

namespace Wikibase\Lexeme\DataModel;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LexemeForm {

	/**
	 * @var LexemeFormId|null
	 */
	private $id;

	/**
	 * @var string
	 */
	private $representation;

	/**
	 * @param LexemeFormId $id|null
	 * @param string $representation
	 */
	public function __construct( LexemeFormId $id = null, $representation ) {
		$this->id = $id;
		$this->representation = $representation;
	}

	/**
	 * @return LexemeFormId|null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getRepresentation() {
		return $this->representation;
	}

}
