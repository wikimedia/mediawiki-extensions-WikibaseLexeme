<?php

namespace Wikibase\Lexeme\Tests\TestDoubles;

use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Storage\GetLexemeException;
use Wikibase\Lexeme\Domain\Storage\LexemeRepository;
use Wikibase\Lexeme\Domain\Storage\UpdateLexemeException;

/**
 * @license GPL-2.0-or-later
 */
class FakeLexemeRepository implements LexemeRepository {

	private $lexemes = [];
	private $throwOnRead = false;
	private $throwOnWrite = false;

	public function __construct( /* Lexeme */ ...$lexemes ) {
		foreach ( $lexemes as $lexeme ) {
			$this->updateLexeme( $lexeme, '' );
		}
	}

	public function updateLexeme( Lexeme $lexeme, string $editSummary ) {
		if ( $this->throwOnWrite ) {
			throw new UpdateLexemeException();
		}

		$this->lexemes[$lexeme->getId()->serialize()] = clone $lexeme;
	}

	public function getLexemeById( LexemeId $id ) {
		if ( $this->throwOnRead ) {
			throw new GetLexemeException();
		}

		if ( array_key_exists( $id->serialize(), $this->lexemes ) ) {
			return $this->lexemes[$id->serialize()];
		}

		return null;
	}

	public function throwOnRead() {
		$this->throwOnRead = true;
	}

	public function throwOnWrite() {
		$this->throwOnWrite = true;
	}

}
