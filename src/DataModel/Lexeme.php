<?php

namespace Wikibase\Lexeme\DataModel;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0+
 */
class Lexeme implements EntityDocument, StatementListProvider {

	const ENTITY_TYPE = 'lexeme';

	/**
	 * @var LexemeId|null
	 */
	private $id;

	/**
	 * @var StatementList
	 */
	private $statements;

	/**
	 * @var TermList
	 */
	private $lemmas;

	/**
	 * @var ItemId|null
	 */
	private $lexicalCategory;

	/**
	 * @var ItemId|null
	 */
	private $language;

	/**
	 * @param LexemeId|null $id
	 * @param TermList|null $lemmas
	 * @param ItemId|null $lexicalCategory
	 * @param ItemId|null $language
	 * @param StatementList|null $statements
	 */
	public function __construct(
		LexemeId $id = null,
		TermList $lemmas = null,
		ItemId $lexicalCategory = null,
		ItemId $language = null,
		StatementList $statements = null
	) {
		$this->id = $id;
		$this->lemmas = $lemmas ?: new TermList();
		$this->lexicalCategory = $lexicalCategory;
		$this->language = $language;
		$this->statements = $statements ?: new StatementList();
	}

	/**
	 * @return EntityId|null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return self::ENTITY_TYPE;
	}

	/**
	 * @return StatementList
	 */
	public function getStatements() {
		return $this->statements;
	}

	/**
	 * @param LexemeId|int $id
	 *
	 * @throws InvalidArgumentException
	 */
	public function setId( $id ) {
		if ( $id instanceof LexemeId ) {
			$this->id = $id;
		} elseif ( is_int( $id ) ) {
			$this->id = new LexemeId( 'L' . $id );
		} else {
			throw new InvalidArgumentException(
				'$id must be an instance of LexemeId or an integer.'
			);
		}
	}

	/**
	 * @return bool
	 */
	public function isEmpty() {
		return $this->lemmas->isEmpty()
			&& $this->lexicalCategory === null
			&& $this->language === null
			&& $this->statements->isEmpty();
	}

	/**
	 * @see EntityDocument::equals
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		if ( $this === $target ) {
			return true;
		}

		if ( !( $target instanceof self ) ) {
			return false;
		}

		$sameLemmas = $this->lemmas->equals( $target->lemmas );

		$sameLexicalCategory = $this->lexicalCategory === $target->lexicalCategory
			|| ( $this->lexicalCategory !== null
				&& $this->lexicalCategory->equals( $target->lexicalCategory )
			);

		$sameLanguage = $this->language === $target->language
			|| ( $this->language !== null
				&& $this->language->equals( $target->language )
			);

		return $sameLemmas
			&& $sameLexicalCategory
			&& $sameLanguage
			&& $this->statements->equals( $target->statements );
	}

	/**
	 * @see EntityDocument::copy
	 *
	 * @return Lexeme
	 */
	public function copy() {
		return clone $this;
	}

	/**
	 * @see http://php.net/manual/en/language.oop5.cloning.php
	 */
	public function __clone() {
		$this->lemmas = clone $this->lemmas;
		$this->statements = clone $this->statements;
	}

	/**
	 * @return TermList
	 */
	public function getLemmas() {
		return $this->lemmas;
	}

	/**
	 * @param TermList $lemmas
	 */
	public function setLemmas( TermList $lemmas ) {
		$this->lemmas = $lemmas;
	}

	/**
	 * @return ItemId|null
	 */
	public function getLexicalCategory() {
		return $this->lexicalCategory;
	}

	/**
	 * @param ItemId $lexicalCategory
	 */
	public function setLexicalCategory( ItemId $lexicalCategory ) {
		$this->lexicalCategory = $lexicalCategory;
	}

	/**
	 * @return ItemId|null
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * @param ItemId $language
	 */
	public function setLanguage( ItemId $language ) {
		$this->language = $language;
	}

}
