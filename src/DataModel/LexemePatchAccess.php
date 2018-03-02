<?php

namespace Wikibase\Lexeme\DataModel;

/**
 * @license GPL-2.0-or-later
 */
class LexemePatchAccess {

	/**
	 * @var int
	 */
	private $nextFormId;

	/**
	 * @var FormSet
	 */
	private $forms;

	private $isClosed = false;

	/**
	 * @param int $nextFormId
	 * @param FormSet $forms
	 */
	public function __construct( $nextFormId, FormSet $forms ) {
		if ( !is_int( $nextFormId ) || $nextFormId < 1 ) {
			throw new \InvalidArgumentException(
				"nextFormId should be positive integer. Given: {$nextFormId}"
			);
		}

		$this->nextFormId = $nextFormId;
		$this->forms = clone $forms;
	}

	public function addForm( Form $form ) {
		$this->assertIsNotClosed();

		$this->forms->add( $form );
	}

	/**
	 * @param int $number
	 */
	public function increaseNextFormIdTo( $number ) {
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

	public function close() {
		$this->isClosed = true;
	}

	/**
	 * @return FormSet
	 */
	public function getForms() {
		return $this->forms;
	}

	/**
	 * @return int
	 */
	public function getNextFormId() {
		return $this->nextFormId;
	}

	private function assertIsNotClosed() {
		if ( $this->isClosed ) {
			throw new \LogicException( "Cannot modify closed LexemePatchAccess" );
		}
	}

}
