<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class LexemePatchAccess {

	private int $nextFormId;

	private FormSet $forms;

	private int $nextSenseId;

	private SenseSet $senses;

	private bool $isClosed = false;

	public function __construct( int $nextFormId, FormSet $forms, int $nextSenseId, SenseSet $senses ) {
		if ( $nextFormId < 1 ) {
			throw new \InvalidArgumentException(
				"nextFormId should be positive integer. Given: {$nextFormId}"
			);
		}
		if ( $nextSenseId < 1 ) {
			throw new \InvalidArgumentException(
				"nextSenseId should be positive integer. Given: {$nextSenseId}"
			);
		}

		$this->nextFormId = $nextFormId;
		$this->forms = clone $forms;
		$this->nextSenseId = $nextSenseId;
		$this->senses = clone $senses;
	}

	public function addForm( Form $form ): void {
		$this->assertIsNotClosed();

		$this->forms->add( $form );
	}

	public function increaseNextFormIdTo( int $number ): void {
		if ( $number < $this->nextFormId ) {
			throw new \LogicException(
				"Cannot increase `nextFormId` because given number is less than counter value " .
				"of this Lexeme. Current=`{$this->nextFormId}`, given=`{$number}`"
			);
		}

		$this->nextFormId = $number;
	}

	public function addSense( Sense $sense ): void {
		$this->assertIsNotClosed();

		$this->senses->add( $sense );
	}

	public function increaseNextSenseIdTo( int $number ): void {
		if ( $number < $this->nextSenseId ) {
			throw new \LogicException(
				"Cannot increase `nextSenseId` because given number is less than counter value " .
				"of this Lexeme. Current=`{$this->nextSenseId}`, given=`{$number}`"
			);
		}

		$this->nextSenseId = $number;
	}

	public function close(): void {
		$this->isClosed = true;
	}

	public function getForms(): FormSet {
		return $this->forms;
	}

	public function getNextFormId(): int {
		return $this->nextFormId;
	}

	public function getSenses(): SenseSet {
		return $this->senses;
	}

	public function getNextSenseId(): int {
		return $this->nextSenseId;
	}

	private function assertIsNotClosed(): void {
		if ( $this->isClosed ) {
			throw new \LogicException( "Cannot modify closed LexemePatchAccess" );
		}
	}

}
