<?php

namespace Wikibase\Lexeme\Domain\Model;

use Countable;
use Wikibase\Lexeme\Domain\Model\Exceptions\ConflictException;

/**
 * Set of Forms in which uniqueness of a Form is controlled by it's ID.
 * Supposed to be used only inside the Lexeme class
 *
 * @license GPL-2.0-or-later
 */
class FormSet implements Countable {

	/**
	 * @var Form[] indexed by serialization of FormId
	 */
	private $forms = [];

	/**
	 * @param Form[] $forms
	 */
	public function __construct( array $forms = [] ) {
		foreach ( $forms as $form ) {
			if ( !$form instanceof Form ) {
				throw new \InvalidArgumentException( '$forms must be an array of Forms' );
			}

			$this->add( $form );
		}
	}

	/**
	 * @return Form[]
	 */
	public function toArray() {
		$forms = $this->sortForms( $this->forms );
		return array_values( $forms );
	}

	/**
	 * Return the individual Forms in arbitrary order.
	 *
	 * Only use this method if the order is certainly insignificant,
	 * e.g. because the Forms will be summarized or reduced in some way.
	 * Otherwise, use {@link toArray()}.
	 *
	 * @return Form[]
	 */
	public function toArrayUnordered(): array {
		return array_values( $this->forms );
	}

	/**
	 * @param Form[] $forms
	 * @return array sorted array mapping numeric id to the form
	 */
	private function sortForms( array $forms ) {
		$sortedForms = [];
		foreach ( $forms as $form ) {
			$formIdPart = explode( '-', $form->getId()->getSerialization(), 2 )[1];
			$formIdNumber = (int)substr( $formIdPart, 1 );
			$sortedForms[$formIdNumber] = $form;
		}
		ksort( $sortedForms );

		return $sortedForms;
	}

	/**
	 * @return int
	 */
	public function count(): int {
		return count( $this->forms );
	}

	/**
	 * @return int
	 */
	public function maxFormIdNumber() {
		if ( empty( $this->forms ) ) {
			return 0;
		}

		$numbers = array_map( static function ( $formId ) {
			list( , $formId ) = explode( '-', $formId, 2 );
			return (int)substr( $formId, 1 );
		}, array_keys( $this->forms ) );
		return max( $numbers );
	}

	public function add( Form $form ) {
		$formId = $form->getId()->getSerialization();
		if ( isset( $this->forms[$formId] ) ) {
			throw new ConflictException(
				'At least two forms with the same ID were provided: `' . $formId . '`'
			);
		}

		$this->forms[$formId] = $form;
	}

	public function remove( FormId $formId ) {
		unset( $this->forms[$formId->getSerialization()] );
	}

	/**
	 * Replace the form identified by $form->getId() with the given one or add it
	 *
	 * @param Form $form
	 */
	public function put( Form $form ) {
		$this->remove( $form->getId() );
		$this->add( $form );
	}

	/**
	 * @param FormId $formId
	 *
	 * @return Form|null
	 */
	public function getById( FormId $formId ) {
		return $this->forms[$formId->getSerialization()] ?? null;
	}

	/**
	 * @return self
	 */
	public function copy() {
		return clone $this;
	}

	/**
	 * @see http://php.net/manual/en/language.oop5.cloning.php
	 */
	public function __clone() {
		$clonedForms = [];
		foreach ( $this->forms as $key => $form ) {
			$clonedForms[$key] = clone $form;
		}
		$this->forms = $clonedForms;
	}

	/**
	 * @return bool
	 */
	public function isEmpty() {
		return empty( $this->forms );
	}

	public function equals( $other ) {
		if ( $this === $other ) {
			return true;
		}

		if ( !( $other instanceof self ) ) {
			return false;
		}

		return $this->sameForms( $other );
	}

	/**
	 * @param FormId $formId
	 * @return bool
	 */
	public function hasFormWithId( FormId $formId ) {
		return $this->getById( $formId ) !== null;
	}

	/**
	 * @return bool
	 */
	private function sameForms( FormSet $other ) {
		if ( $this->count() !== $other->count() ) {
			return false;
		}

		foreach ( $this->forms as $form ) {
			if ( !$form->equals( $other->getById( $form->getId() ) ) ) {
				return false;
			}
		}

		return true;
	}

}
