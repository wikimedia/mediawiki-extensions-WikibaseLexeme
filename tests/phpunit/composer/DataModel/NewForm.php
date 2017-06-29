<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;

class NewForm {

	/**
	 * @var FormId
	 */
	private $formId;

	/**
	 * @var string
	 */
	private $representation;

	private function __construct() {
		$this->formId = $this->newRandomItemId();
	}

	/**
	 * @param string $representation
	 * @return self
	 */
	public static function havingRepresentation( $representation ) {
		$result = new self();
		return $result->andRepresentation( $representation );
	}

	/**
	 * @return Form
	 */
	public function build() {
		return new Form( $this->formId, $this->representation, [] );
	}

	private function newRandomItemId() {
		return new FormId( 'F' . mt_rand( 1, mt_getrandmax() ) );
	}

	public function andRepresentation( $representation ) {
		$result = clone $this;
		$result->representation = $representation;
		return $result;
	}

}
