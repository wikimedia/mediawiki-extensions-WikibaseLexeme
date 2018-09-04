<?php

namespace Wikibase\Lexeme\DataTransfer;

use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DataModel\SenseId;

/**
 * A sense that has not yet been assigned an ID.
 * Its gloss list starts out empty, but may later be populated.
 * It may also be associated with a particular lexeme.
 *
 * @license GPL-2.0-or-later
 */
class BlankSense extends Sense {

	/**
	 * @var Lexeme
	 */
	private $lexeme;

	public function __construct() {
		$this->id = null;
		$this->glossList = new TermList();
		$this->statementList = new StatementList();
	}

	public function getId() {
		if ( $this->id !== null ) {
			return $this->id;
		}

		if ( $this->lexeme !== null ) {
			return new DummySenseId( $this->lexeme->getId() );
		}

		return new NullSenseId();
	}

	public function setLexeme( Lexeme $lexeme ) {
		$this->lexeme = $lexeme;
	}

	public function setGlosses( TermList $glosses ) {
		$this->glossList = $glosses;
	}

	public function getRealSense( SenseId $senseId ) {
		$this->id = $senseId;

		return new Sense( $senseId, $this->glossList, $this->statementList );
	}

	public function __clone() {
		$this->glossList = clone $this->glossList;
		$this->statementList = clone $this->statementList;
	}

}
