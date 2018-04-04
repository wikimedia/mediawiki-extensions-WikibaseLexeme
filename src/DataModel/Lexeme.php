<?php

namespace Wikibase\Lexeme\DataModel;

use InvalidArgumentException;
use OutOfRangeException;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\TermList;

/**
 * Mutable (e.g. the provided StatementList can be changed) implementation of a Lexeme in the
 * lexiographical data model.
 *
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLexeme/Data_Model#Lexeme
 *
 * @license GPL-2.0-or-later
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
	 * @var FormSet
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
	 * @param FormSet|null $forms
	 * @param Sense[] $senses
	 */
	public function __construct(
		LexemeId $id = null,
		TermList $lemmas = null,
		ItemId $lexicalCategory = null,
		ItemId $language = null,
		StatementList $statements = null,
		$nextFormId = 1,
		FormSet $forms = null,
		array $senses = []
	) {
		$this->id = $id;
		$this->lemmas = $lemmas ?: new TermList();
		$this->lexicalCategory = $lexicalCategory;
		$this->language = $language;
		$this->statements = $statements ?: new StatementList();
		$this->forms = $forms ?: new FormSet( [] );
		// TODO: Add assertion on Senses types.
		$this->senses = $senses;

		$this->assertCorrectNextFormIdIsGiven( $nextFormId, $this->forms );
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

		$sameFormIdCounter = $this->nextFormId === $target->nextFormId;
		$sameForms = $this->forms == $target->forms;

		return $this->lemmas->equals( $target->lemmas )
			&& $sameLexicalCategory
			&& $sameLanguage
			&& $sameFormIdCounter
			&& $sameForms
			&& $this->statements->equals( $target->statements );
	}

	/**
	 * @see EntityDocument::copy
	 *
	 * @return self
	 */
	public function copy() {
		return clone $this;
	}

	/**
	 * @see http://php.net/manual/en/language.oop5.cloning.php
	 */
	public function __clone() {
		// TermList is mutable, but Term is not. No deeper cloning necessary.
		$this->lemmas = clone $this->lemmas;
		$this->statements = clone $this->statements;
		$this->forms = clone $this->forms;
		foreach ( $this->senses as &$sense ) {
			$sense = clone $sense;
		}
	}

	/**
	 * @return TermList
	 */
	public function getLemmas() {
		return $this->lemmas;
	}

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

	public function setLanguage( ItemId $language ) {
		$this->language = $language;
	}

	/**
	 * @return FormSet
	 */
	public function getForms() {
		return $this->forms;
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
			&& $this->lexicalCategory !== null
			&& $this->lemmas !== null
			&& !$this->lemmas->isEmpty();
	}

	/**
	 * @return int
	 */
	public function getNextFormId() {
		return $this->nextFormId;
	}

	/**
	 * @param FormId $formId
	 *
	 * @throws OutOfRangeException
	 * @return Form
	 */
	public function getForm( FormId $formId ) {
		$form = $this->forms->getById( $formId );

		if ( $form === null ) {
			throw new OutOfRangeException(
				"Lexeme {$this->id->getSerialization()} doesn't have Form " .
				$formId->getSerialization()
			);
		}

		return $form;
	}

	/**
	 * @param TermList $representations
	 * @param ItemId[] $grammaticalFeatures
	 *
	 * @return Form
	 */
	public function addForm( TermList $representations, array $grammaticalFeatures ) {
		if ( !$this->id ) {
			throw new \LogicException( 'Can not add forms to a lexeme with no ID' );
		}

		$formId = new FormId( $this->id->getSerialization() . '-F' . $this->nextFormId++ );
		$form = new Form( $formId, $representations, $grammaticalFeatures );
		$this->forms->add( $form );

		return $form;
	}

	public function removeForm( FormId $formId ) {
		$this->forms->remove( $formId );
	}

	/**
	 * @param int $number
	 */
	private function increaseNextFormIdTo( $number ) {
		if ( !is_int( $number ) ) {
			throw new \InvalidArgumentException( '$nextFormId` must be integer' );
		}

		if ( $number < $this->nextFormId ) {
			throw new \LogicException(
				"Cannot increase `nextFormId` because given number is less than counter value " .
				"of this Lexeme. Current=`{$this->nextFormId}`, given=`{$number}`"
			);
		}

		$this->nextFormId = $number;
	}

	public function patch( callable $patcher ) {
		$lexemePatchAccess = new LexemePatchAccess( $this->nextFormId, $this->forms );
		try {
			$patcher( $lexemePatchAccess );
		} finally {
			$lexemePatchAccess->close();
		}
		$newFormSet = $lexemePatchAccess->getForms();
		$newNextFormId = $lexemePatchAccess->getNextFormId();

		$this->assertCorrectNextFormIdIsGiven( $newNextFormId, $newFormSet );

		$this->increaseNextFormIdTo( $newNextFormId );
		$this->forms = $newFormSet;
	}

	/**
	 * @param int $nextFormId
	 * @param FormSet $formSet
	 */
	private function assertCorrectNextFormIdIsGiven( $nextFormId, FormSet $formSet ) {
		if ( !is_int( $nextFormId ) || $nextFormId < 1 ) {
			throw new \InvalidArgumentException( '$nextFormId should be a positive integer' );
		}

		if ( $nextFormId <= $formSet->count() ) {
			throw new \LogicException(
				sprintf(
					'$nextFormId must always be greater than the number of Forms. ' .
					'$nextFormId = `%s`, number of forms = `%s`',
					$nextFormId,
					$formSet->count()
				)
			);
		}

		if ( $nextFormId <= $formSet->maxFormIdNumber() ) {
			throw new \LogicException(
				sprintf(
					'$nextFormId must always be greater than the max ID number of provided Forms. ' .
					'$nextFormId = `%s`, max ID number of provided Forms = `%s`',
					$nextFormId,
					$formSet->maxFormIdNumber()
				)
			);
		}
	}

}
