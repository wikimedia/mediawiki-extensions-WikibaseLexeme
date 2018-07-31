<?php

namespace Wikibase\Lexeme\DataModel;

use LogicException;
use Wikibase\DataModel\Entity\ClearableEntity;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\TermList;

/**
 * Mutable (e.g. the provided StatementList can be changed) implementation of a Lexeme's sense in
 * the lexiographical data model.
 *
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLexeme/Data_Model#Sense
 *
 * @license GPL-2.0-or-later
 */
class Sense implements EntityDocument, StatementListProvider, ClearableEntity {

	const ENTITY_TYPE = 'sense';

	/**
	 * @var SenseId
	 */
	private $id;

	/**
	 * @var TermList
	 */
	protected $glossList;

	/**
	 * @var StatementList
	 */
	protected $statementList;

	public function __construct(
		SenseId $id,
		TermList $glossList,
		StatementList $statementList = null
	) {
		$this->id = $id;
		$this->glossList = $glossList; // TODO: check there is at least gloss in one language provided
		$this->statementList = $statementList ?: new StatementList();
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

	/**
	 * @see EntityDocument::copy
	 *
	 * @since 0.1
	 *
	 * @return self
	 */
	public function copy() {
		return clone $this;
	}

	/**
	 * @see http://php.net/manual/en/language.oop5.cloning.php
	 *
	 * @since 5.1
	 */
	public function __clone() {
		// TermList is mutable, but Term is not. No deeper cloning necessary.
		$this->glossList = clone $this->glossList;
		$this->statementList = clone $this->statementList;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return 'sense';
	}

	/**
	 * @param SenseId $id
	 *
	 * @throws LogicException always
	 */
	public function setId( $id ) {
		throw new LogicException( 'Setting the ID of a Sense is currently not implemented, and '
			. 'might not be needed any more, except when implementing the "clear" feature of the '
			. '"wbeditentity" API' );
	}

	/**
	 * @see EntityDocument::isEmpty
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return $this->glossList->isEmpty()
			&& $this->statementList->isEmpty();
	}

	/**
	 * @see EntityDocument::equals
	 *
	 * @param mixed $target
	 *
	 * @return bool True if the sense's contents are equal. Does not consider the ID.
	 */
	public function equals( $target ) {
		if ( $this === $target ) {
			return true;
		}

		return $target instanceof self
			&& $this->glossList->equals( $target->glossList )
			&& $this->statementList->equals( $target->statementList );
	}

	/**
	 * Clears the glosses and statements of a sense.
	 * Note that this leaves the sense in an insufficiently initialized state.
	 */
	public function clear() {
		$this->glossList = new TermList();
		$this->statementList = new StatementList();
	}

}
