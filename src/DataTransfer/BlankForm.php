<?php

namespace Wikibase\Lexeme\DataTransfer;

use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;

/**
 * @license GPL-2.0-or-later
 */
class BlankForm extends Form {

	/**
	 * @var Lexeme
	 */
	private $lexeme;

	public function __construct() {
		$this->representations = new TermList();
		$this->grammaticalFeatures = [];
		$this->statementList = new StatementList();
	}

	public function getId() {
		if ( $this->lexeme === null ) {
			return new NullFormId();
		}

		return new DummyFormId( $this->lexeme->getId() );
	}

	public function setLexeme( Lexeme $lexeme ) {
		$this->lexeme = $lexeme;
	}

	public function getRealForm( FormId $formId ) {
		return new Form(
			$formId,
			$this->representations, $this->grammaticalFeatures, $this->statementList
		);
	}

	public function __clone() {
		$this->representations = clone $this->representations;
		$this->statementList = clone $this->statementList;
	}

}
