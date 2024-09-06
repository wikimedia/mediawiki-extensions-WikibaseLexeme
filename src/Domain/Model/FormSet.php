<?php

declare( strict_types = 1 );

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
	private array $forms = [];

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
	public function toArray(): array {
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
	 * @return Form[] sorted array mapping numeric id to the form
	 */
	private function sortForms( array $forms ): array {
		$sortedForms = [];
		foreach ( $forms as $form ) {
			$formIdPart = explode( '-', $form->getId()->getSerialization(), 2 )[1];
			$formIdNumber = (int)substr( $formIdPart, 1 );
			$sortedForms[$formIdNumber] = $form;
		}
		ksort( $sortedForms );

		return $sortedForms;
	}

	public function count(): int {
		return count( $this->forms );
	}

	public function maxFormIdNumber(): int {
		if ( !$this->forms ) {
			return 0;
		}

		$numbers = array_map( static function ( $formId ) {
			[ , $formId ] = explode( '-', $formId, 2 );
			return (int)substr( $formId, 1 );
		}, array_keys( $this->forms ) );
		return max( $numbers );
	}

	public function add( Form $form ): void {
		$formId = $form->getId()->getSerialization();
		if ( isset( $this->forms[$formId] ) ) {
			throw new ConflictException(
				'At least two forms with the same ID were provided: `' . $formId . '`'
			);
		}

		$this->forms[$formId] = $form;
	}

	public function remove( FormId $formId ): void {
		unset( $this->forms[$formId->getSerialization()] );
	}

	/**
	 * Replace the form identified by $form->getId() with the given one or add it
	 */
	public function put( Form $form ): void {
		$this->remove( $form->getId() );
		$this->add( $form );
	}

	public function getById( FormId $formId ): ?Form {
		return $this->forms[$formId->getSerialization()] ?? null;
	}

	public function copy(): self {
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

	public function isEmpty(): bool {
		return !$this->forms;
	}

	public function equals( $other ): bool {
		if ( $this === $other ) {
			return true;
		}

		if ( !( $other instanceof self ) ) {
			return false;
		}

		return $this->sameForms( $other );
	}

	public function hasFormWithId( FormId $formId ): bool {
		return $this->getById( $formId ) !== null;
	}

	private function sameForms( FormSet $other ): bool {
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
