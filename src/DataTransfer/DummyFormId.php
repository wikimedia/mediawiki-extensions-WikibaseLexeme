<?php

namespace Wikibase\Lexeme\DataTransfer;

use LogicException;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
class DummyFormId extends FormId {

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
		return $this->stemsFromNewlyCreatedForm( $target )
			|| $target->getLexemeId() === $this->getLexemeId();
	}

	/**
	 * @param mixed $target
	 * @return bool
	 */
	private function stemsFromNewlyCreatedForm( $target ) {
		return $target instanceof NullFormId;
	}

}
