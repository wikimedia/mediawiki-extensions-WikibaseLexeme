<?php

namespace Wikibase\Lexeme\Tests\TestDoubles;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @covers \Wikibase\Lexeme\Tests\TestDoubles\FakeLexemeRepository
 *
 * @license GPL-2.0-or-later
 */
class FakeLexemeRepositoryTest extends TestCase {

	public function testModifyingLexemeAfterStoringItDoesNotAffectStoredLexeme() {
		$lexeme = new Lexeme( new LexemeId( 'L1' ) );
		$repo = new FakeLexemeRepository( $lexeme );

		$lexeme->getLemmas()->setTerm( new Term( 'en', 'text' ) );

		$this->assertCount(
			0,
			$repo->getLexemeById( $lexeme->getId() )->getLemmas()
		);
	}

}
