<?php

namespace Wikibase\Lexeme\DataModel;

use InvalidArgumentException;
use UnexpectedValueException;
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
	 * @var Form[]
	 */
	private $forms;

	/**
	 * @var Sense[]
	 */
	private $senses;

	/**
	 * @var int
	 */
	private $nextFormId = 1;

	/**
	 * Note that $lexicalCategory and $language can only be null during construction time. Their
	 * setters can not be called with null, and their getters will throw an exception if the
	 * corresponding field was never initialized.
	 *
	 * @param LexemeId|null $id
	 * @param TermList|null $lemmas
	 * @param ItemId|null $lexicalCategory
	 * @param ItemId|null $language
	 * @param StatementList|null $statements
	 * @param int $nextFormId
	 * @param Form[] $forms
	 * @param Sense[] $senses
	 */
	public function __construct(
		LexemeId $id = null,
		TermList $lemmas = null,
		ItemId $lexicalCategory = null,
		ItemId $language = null,
		StatementList $statements = null,
		$nextFormId = 1,
		array $forms = [],
		array $senses = []
	) {
		$this->id = $id;
		$this->lemmas = $lemmas ?: new TermList();
		$this->lexicalCategory = $lexicalCategory;
		$this->language = $language;
		$this->statements = $statements ?: new StatementList();
		//TODO add assertion on Forms and Senses types
		$this->forms = $forms;
		$this->senses = $senses;
		//FIXME: Add assertions regarding $nextFormId:
		// * int
		// * >=1
		// * > max FormId in provided form list
		// * > form count
		$this->nextFormId = $nextFormId;
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
	 * @param LexemeId $id
	 *
	 * @throws InvalidArgumentException
	 */
	public function setId( $id ) {
		if ( $id instanceof LexemeId ) {
			$this->id = $id;
		} else {
			throw new InvalidArgumentException(
				'$id must be an instance of LexemeId.'
			);
		}
	}

	/**
	 * @return bool A entity is empty if it does not contain any content that can be removed. Note
	 *  that neither ID nor lexical category nor language can be set to null, and are therefor not
	 *  taken into account.
	 */
	public function isEmpty() {
		return $this->lemmas->isEmpty()
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

		$sameLexicalCategory = $this->lexicalCategory === $target->lexicalCategory
			|| ( $this->lexicalCategory !== null
				&& $this->lexicalCategory->equals( $target->lexicalCategory ) );

		$sameLanguage = $this->language === $target->language
			|| ( $this->language !== null
				&& $this->language->equals( $target->language ) );

		$sameForms = $this->forms == $target->forms;

		return $this->lemmas->equals( $target->lemmas )
			&& $sameLexicalCategory
			&& $sameLanguage
			&& $sameForms
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
	 * @throws UnexpectedValueException when the object was constructed with $lexicalCategory set to
	 * null, and the field was never initialized since then.
	 * @return ItemId
	 */
	public function getLexicalCategory() {
		if ( !$this->lexicalCategory ) {
			throw new UnexpectedValueException( 'Can not access uninitialized field' );
		}

		return $this->lexicalCategory;
	}

	/**
	 * @param ItemId $lexicalCategory
	 */
	public function setLexicalCategory( ItemId $lexicalCategory ) {
		$this->lexicalCategory = $lexicalCategory;
	}

	/**
	 * @throws UnexpectedValueException when the object was constructed with $language set to null,
	 * and the field was never initialized since then.
	 * @return ItemId
	 */
	public function getLanguage() {
		if ( !$this->language ) {
			throw new UnexpectedValueException( 'Can not access uninitialized field' );
		}

		return $this->language;
	}

	/**
	 * @param ItemId $language
	 */
	public function setLanguage( ItemId $language ) {
		$this->language = $language;
	}

	/**
	 * @return Form[]
	 */
	public function getForms() {
		return $this->forms;
	}

	/**
	 * @param Form[] $forms
	 * @deprecated Temporary method, for demo. Just don't use.
	 */
	public function setForms( array $forms ) {
		$this->forms = $forms;
	}

	/**
	 * @return Sense[]
	 */
	public function getSenses() {
		return $this->senses;
	}

	/**
	 * @param Sense[] $senses
	 * @deprecated Only for demonstration purposes. Do not use otherwise!
	 */
	public function setSenses( array $senses ) {
		$this->senses = $senses;
	}

	/**
	 * @return bool False if a non-optional field was never initialized, true otherwise.
	 */
	public function isSufficientlyInitialized() {
		return $this->id !== null
			&& $this->language !== null
			&& $this->lexicalCategory !== null;
	}

	/**
	 * @return int
	 */
	public function getNextFormId() {
		return $this->nextFormId;
	}

	/**
	 * @param TermList $representations
	 * @param ItemId[] $grammaticalFeatures
	 *
	 * @return Form
	 */
	public function addForm( TermList $representations, array $grammaticalFeatures ) {
		$formId = new FormId( 'F' . $this->nextFormId++ );
		$form = new Form( $formId, $representations, $grammaticalFeatures );
		$this->forms[] = $form;

		return $form;
	}

}
