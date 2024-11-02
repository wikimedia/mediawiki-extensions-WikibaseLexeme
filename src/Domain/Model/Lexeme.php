<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model;

use InvalidArgumentException;
use OutOfRangeException;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\ClearableEntity;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;

/**
 * Mutable (e.g. the provided StatementList can be changed) implementation of a Lexeme in the
 * lexicographical data model.
 *
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLexeme/Data_Model#Lexeme
 *
 * @license GPL-2.0-or-later
 */
class Lexeme implements StatementListProvidingEntity, ClearableEntity {

	public const ENTITY_TYPE = 'lexeme';

	private ?LexemeId $id;

	private StatementList $statements;

	private TermList $lemmas;

	private ?ItemId $lexicalCategory;

	private ?ItemId $language;

	private FormSet $forms;

	private SenseSet $senses;

	private int $nextFormId;

	private int $nextSenseId;

	/**
	 * Note that $lexicalCategory and $language can only be null during construction time. Their
	 * setters can not be called with null, and their getters will throw an exception if the
	 * corresponding field was never initialized.
	 */
	public function __construct(
		?LexemeId $id = null,
		?TermList $lemmas = null,
		?ItemId $lexicalCategory = null,
		?ItemId $language = null,
		?StatementList $statements = null,
		int $nextFormId = 1,
		?FormSet $forms = null,
		int $nextSenseId = 1,
		?SenseSet $senses = null
	) {
		$this->id = $id;
		$this->lemmas = $lemmas ?: new TermList();
		$this->lexicalCategory = $lexicalCategory;
		$this->language = $language;
		$this->statements = $statements ?: new StatementList();
		$this->forms = $forms ?: new FormSet( [] );
		$this->senses = $senses ?: new SenseSet( [] );

		$this->assertCorrectNextFormIdIsGiven( $nextFormId, $this->forms );
		$this->nextFormId = $nextFormId;
		$this->assertCorrectNextSenseIdIsGiven( $nextSenseId, $this->senses );
		$this->nextSenseId = $nextSenseId;
	}

	public function getId(): ?LexemeId {
		return $this->id;
	}

	public function getType(): string {
		return self::ENTITY_TYPE;
	}

	public function getStatements(): StatementList {
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
	public function isEmpty(): bool {
		return $this->lemmas->isEmpty()
			&& $this->statements->isEmpty()
			&& $this->forms->isEmpty()
			&& $this->senses->isEmpty();
	}

	/**
	 * @see EntityDocument::equals
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ): bool {
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
		$sameForms = $this->forms->equals( $target->forms );
		$sameSenseIdCounter = $this->nextSenseId === $target->nextSenseId;
		$sameSenses = $this->senses->equals( $target->senses );

		return $this->lemmas->equals( $target->lemmas )
			&& $sameLexicalCategory
			&& $sameLanguage
			&& $sameFormIdCounter
			&& $sameForms
			&& $sameSenseIdCounter
			&& $sameSenses
			&& $this->statements->equals( $target->statements );
	}

	public function copy(): self {
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
		$this->senses = clone $this->senses;
	}

	public function getLemmas(): TermList {
		return $this->lemmas;
	}

	public function setLemmas( TermList $lemmas ): void {
		$this->lemmas = $lemmas;
	}

	/**
	 * @throws UnexpectedValueException when the object was constructed with $lexicalCategory set to
	 * null, and the field was never initialized since then.
	 */
	public function getLexicalCategory(): ItemId {
		if ( !$this->lexicalCategory ) {
			throw new UnexpectedValueException( 'Can not access uninitialized field' );
		}

		return $this->lexicalCategory;
	}

	public function setLexicalCategory( ?ItemId $lexicalCategory ): void {
		$this->lexicalCategory = $lexicalCategory;
	}

	/**
	 * @throws UnexpectedValueException when the object was constructed with $language set to null,
	 * and the field was never initialized since then.
	 */
	public function getLanguage(): ItemId {
		if ( !$this->language ) {
			throw new UnexpectedValueException( 'Can not access uninitialized field' );
		}

		return $this->language;
	}

	public function setLanguage( ?ItemId $language ): void {
		$this->language = $language;
	}

	public function getForms(): FormSet {
		return $this->forms;
	}

	public function getSenses(): SenseSet {
		return $this->senses;
	}

	/**
	 * @return bool False if a non-optional field was never initialized, true otherwise.
	 */
	public function isSufficientlyInitialized(): bool {
		return $this->id !== null
			&& $this->language !== null
			&& $this->lexicalCategory !== null
			&& !$this->lemmas->isEmpty();
	}

	public function getNextFormId(): int {
		return $this->nextFormId;
	}

	public function getNextSenseId(): int {
		return $this->nextSenseId;
	}

	/**
	 * @throws OutOfRangeException
	 */
	public function getForm( FormId $formId ): Form {
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
	 * @throws OutOfRangeException if no sense by that ID exists
	 */
	public function getSense( SenseId $senseId ): Sense {
		$sense = $this->senses->getById( $senseId );

		if ( $sense === null ) {
			$lexemeId = $this->id->getSerialization();
			throw new OutOfRangeException(
				"Lexeme {$lexemeId} doesn't have sense {$senseId->getSerialization()}"
			);
		}

		return $sense;
	}

	/**
	 * Replace the form identified by $form->getId() with the given one or add it.
	 *
	 * New form ids are generated for forms with a NullFormId or an unknown DummyFormId.
	 */
	public function addOrUpdateForm( Form $form ): void {
		if ( !$this->id ) {
			throw new \LogicException( 'Can not add forms to a lexeme with no ID' );
		}

		if ( $form instanceof BlankForm && !$this->forms->hasFormWithId( $form->getId() ) ) {
			$form->setId(
				new FormId(
					LexemeSubEntityId::formatSerialization( $this->id, 'F', $this->nextFormId++ )
				)
			);
		}

		$this->forms->put( $form );

		$this->assertCorrectNextFormIdIsGiven( $this->getNextFormId(), $this->getForms() );
	}

	/**
	 * Replace the sense identified by $sense->getId() with the given one or add it.
	 */
	public function addOrUpdateSense( Sense $sense ): void {
		if ( !$this->id ) {
			throw new \LogicException( 'Cannot add sense to a lexeme with no ID' );
		}

		if ( $sense instanceof BlankSense && !$this->senses->hasSenseWithId( $sense->getId() ) ) {
			$sense->setId(
				new SenseId(
					LexemeSubEntityId::formatSerialization( $this->id, 'S', $this->nextSenseId++ )
				)
			);
		}

		$this->senses->put( $sense );
		$this->assertCorrectNextSenseIdIsGiven( $this->getNextSenseId(), $this->getSenses() );
	}

	public function removeForm( FormId $formId ): void {
		$this->forms->remove( $formId );
	}

	public function removeSense( SenseId $senseId ): void {
		$this->senses->remove( $senseId );
	}

	private function increaseNextFormIdTo( int $number ): void {
		if ( $number < $this->nextFormId ) {
			throw new \LogicException(
				"Cannot increase `nextFormId` because given number is less than counter value " .
				"of this Lexeme. Current=`{$this->nextFormId}`, given=`{$number}`"
			);
		}

		$this->nextFormId = $number;
	}

	private function increaseNextSenseIdTo( int $number ): void {
		if ( $number < $this->nextSenseId ) {
			throw new \LogicException(
				"Cannot increase `nextSenseId` because given number is less than counter value " .
				"of this Lexeme. Current=`{$this->nextSenseId}`, given=`{$number}`"
			);
		}

		$this->nextSenseId = $number;
	}

	public function patch( callable $patcher ): void {
		$lexemePatchAccess = new LexemePatchAccess(
			$this->nextFormId,
			$this->forms,
			$this->nextSenseId,
			$this->senses
		);
		try {
			$patcher( $lexemePatchAccess );
		} finally {
			$lexemePatchAccess->close();
		}
		$newFormSet = $lexemePatchAccess->getForms();
		$newNextFormId = $lexemePatchAccess->getNextFormId();
		$newSenseSet = $lexemePatchAccess->getSenses();
		$newNextSenseId = $lexemePatchAccess->getNextSenseId();

		$this->assertCorrectNextFormIdIsGiven( $newNextFormId, $newFormSet );
		$this->assertCorrectNextSenseIdIsGiven( $newNextSenseId, $newSenseSet );

		$this->increaseNextFormIdTo( $newNextFormId );
		$this->forms = $newFormSet;
		$this->increaseNextSenseIdTo( $newNextSenseId );
		$this->senses = $newSenseSet;
	}

	private function assertCorrectNextFormIdIsGiven( int $nextFormId, FormSet $formSet ): void {
		if ( $nextFormId < 1 ) {
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

	private function assertCorrectNextSenseIdIsGiven( int $nextSenseId, SenseSet $senseSet ): void {
		if ( $nextSenseId < 1 ) {
			throw new InvalidArgumentException( '$nextSenseId should be a positive integer' );
		}

		if ( $nextSenseId <= $senseSet->count() ) {
			throw new \LogicException(
				sprintf(
					'$nextSenseId must always be greater than the number of senses. ' .
					'$nextSenseId = `%s`, number of senses = `%s`',
					$nextSenseId,
					count( $senseSet )
				)
			);
		}

		if ( $nextSenseId <= $senseSet->maxSenseIdNumber() ) {
			throw new \LogicException(
				sprintf(
					'$nextSenseId must always be greater than the max ID number of provided senses. ' .
					'$nextSenseId = `%s`, max ID number of provided senses = `%s`',
					$nextSenseId,
					$senseSet->maxSenseIdNumber()
				)
			);
		}
	}

	/**
	 * Clears lemmas, language, lexical category, statements, forms, and senses of the lexeme.
	 * Note that this leaves the lexeme in an insufficiently initialized state.
	 */
	public function clear(): void {
		$this->lemmas = new TermList();
		$this->statements = new StatementList();
		$this->forms = new FormSet( [] );
		$this->senses = new SenseSet( [] );
		$this->language = null;
		$this->lexicalCategory = null;
	}

}
