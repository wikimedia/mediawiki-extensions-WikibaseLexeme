<?php

namespace Wikibase\Lexeme\DataModel;

use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermList;

/**
 * Mutable (e.g. the provided StatementList can be changed) implementation of a Lexeme's sense in
 * the lexiographical data model.
 *
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLexeme/Data_Model#Sense
 *
 * @license GPL-2.0+
 */
class Sense {

	/**
	 * @var SenseId
	 */
	private $id;

	/**
	 * @var TermList
	 */
	private $glossList;

	/**
	 * @var StatementList
	 */
	private $statementList;

	public function __construct( SenseId $id, TermList $glossList, StatementList $statementList ) {
		$this->id = $id;
		$this->glossList = $glossList; // TODO: check there is at least gloss in one language provided
		$this->statementList = $statementList;
	}

	/**
	 * @return SenseId
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return TermList
	 */
	public function getGlosses() {
		return $this->glossList;
	}

	/**
	 * @return StatementList
	 */
	public function getStatements() {
		return $this->statementList;
	}

}
