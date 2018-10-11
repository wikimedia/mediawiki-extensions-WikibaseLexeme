<?php

namespace Wikibase\Lexeme\Domain\Model;

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

	/**
	 * @var int
	 */
	private $nextSenseId;

	/**
	 * @var SenseSet
	 */
	private $senses;

	private $isClosed = false;

	/**
	 * @param int $nextFormId
	 * @param FormSet $forms
	 * @param int $nextSenseId
	 * @param SenseSet $senses
	 */
	public function __construct( $nextFormId, FormSet $forms, $nextSenseId, SenseSet $senses ) {
		if ( !is_int( $nextFormId ) || $nextFormId < 1 ) {
			throw new \InvalidArgumentException(
				"nextFormId should be positive integer. Given: {$nextFormId}"
			);
		}
		if ( !is_int( $nextSenseId ) || $nextSenseId < 1 ) {
			throw new \InvalidArgumentException(
				"nextSenseId should be positive integer. Given: {$nextSenseId}"
			);
		}

		$this->nextFormId = $nextFormId;
		$this->forms = clone $forms;
		$this->nextSenseId = $nextSenseId;
		$this->senses = clone $senses;
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
			throw new \InvalidArgumentException( '$number` must be integer' );
		}

		if ( $number < $this->nextFormId ) {
			throw new \LogicException(
				"Cannot increase `nextFormId` because given number is less than counter value " .
				"of this Lexeme. Current=`{$this->nextFormId}`, given=`{$number}`"
			);
		}

		$this->nextFormId = $number;
	}

	public function addSense( Sense $sense ) {
		$this->assertIsNotClosed();

		$this->senses->add( $sense );
	}

	public function increaseNextSenseIdTo( $number ) {
		if ( !is_int( $number ) ) {
			throw new \InvalidArgumentException( '$number` must be integer' );
		}

		if ( $number < $this->nextSenseId ) {
			throw new \LogicException(
				"Cannot increase `nextSenseId` because given number is less than counter value " .
				"of this Lexeme. Current=`{$this->nextSenseId}`, given=`{$number}`"
			);
		}

		$this->nextSenseId = $number;
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

	/**
	 * @return SenseSet
	 */
	public function getSenses() {
		return $this->senses;
	}

	/**
	 * @return int
	 */
	public function getNextSenseId() {
		return $this->nextSenseId;
	}

	private function assertIsNotClosed() {
		if ( $this->isClosed ) {
			throw new \LogicException( "Cannot modify closed LexemePatchAccess" );
		}
	}

}
