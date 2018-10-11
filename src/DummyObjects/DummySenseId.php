<?php

namespace Wikibase\Lexeme\DummyObjects;

use LogicException;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * An ID for a BlankSense which has already been associated with a particular lexeme.
 *
 * @license GPL-2.0-or-later
 */
class DummySenseId extends SenseId {

	private $lexemeId;

	public function __construct( LexemeId $lexemeId ) {
		$this->lexemeId = $lexemeId;
	}

	public function getLexemeId() {
		return $this->lexemeId;
	}

	public function serialize() {
		throw new LogicException( 'Shall never be called' );
	}

	public function unserialize( $serialized ) {
		throw new LogicException( 'Shall never be called' );
	}

	public function equals( $target ) {
		return $this->stemsFromNewlyCreatedSense( $target )
			|| $target->getLexemeId() === $this->getLexemeId();
	}

	/**
	 * @param mixed $target
	 * @return bool
	 */
	private function stemsFromNewlyCreatedSense( $target ) {
		return $target instanceof NullSenseId;
	}

}
