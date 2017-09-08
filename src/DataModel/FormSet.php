<?php

namespace Wikibase\Lexeme\DataModel;

/**
 * Set of Forms in which uniqueness of a Form is controlled by it's ID.
 * Supposed to be used only inside the Lexeme class
 */
class FormSet {

	/**
	 * @var Form[] indexed by serialization of FormId
	 */
	private $forms = [];

	/**
	 * @param Form[] $forms
	 */
	public function __construct( array $forms ) {
		foreach ( $forms as $form ) {
			if ( !$form instanceof Form ) {
				throw new \InvalidArgumentException( '$forms must be an array of Forms' );
			}
		}

		foreach ( $forms as $form ) {
			$this->add( $form );
		}
	}

	/**
	 * @return Form[]
	 */
	public function toArray() {
		return array_values( $this->forms );
	}

	public function count() {
		return count( $this->forms );
	}

	/**
	 * @return int
	 */
	public function maxFormIdNumber() {
		if ( empty( $this->forms ) ) {
			return 0;
		}

		$numbers = array_map( function ( $formId ){
			return (int)substr( $formId, 1 );
		}, array_keys( $this->forms ) );
		return max( $numbers );
	}

	public function add( Form $form ) {
		$formId = $form->getId()->getSerialization();
		if ( isset( $this->forms[$formId] ) ) {
			throw new \InvalidArgumentException(
				'At least two forms with the same ID were provided: `' . $formId . '`'
			);
		}

		$this->forms[$formId] = $form;
	}

	public function remove( FormId $formId ) {
		unset( $this->forms[$formId->getSerialization()] );
	}

}
