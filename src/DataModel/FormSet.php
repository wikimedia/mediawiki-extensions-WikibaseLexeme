<?php

namespace Wikibase\Lexeme\DataModel;

/**
 * Set of Forms in which uniqueness of a Form is controlled by it's ID.
 * Supposed to be used only inside the Lexeme class
 *
 * @license GPL-2.0+
 */
class FormSet {

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
		$forms = $this->forms;
		// FIXME: Why is this specific order enforced at this point?
		ksort( $forms );
		return array_values( $forms );
	}

	/**
	 * @return int
	 */
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

	/**
	 * @param FormId $formId
	 *
	 * @return Form|null
	 */
	public function getById( FormId $formId ) {
		return isset( $this->forms[$formId->getSerialization()] ) ?
			$this->forms[$formId->getSerialization()]
			: null;
	}

}
