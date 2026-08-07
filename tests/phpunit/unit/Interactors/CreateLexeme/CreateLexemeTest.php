<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\CreateLexeme;

use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexeme;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeRequest;

/**
 * @covers \Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexeme
 *
 * @license GPL-2.0-or-later
 */
class CreateLexemeTest extends MediaWikiUnitTestCase {

	public function testExecuteCreatesLexeme(): void {
		$response = ( new CreateLexeme() )->execute( new CreateLexemeRequest( [] ) );

		$this->assertEquals( new LexemeId( 'L1' ), $response->lexeme->id );
		$this->assertEquals( new Forms(), $response->lexeme->forms );
		$this->assertEquals( new Senses(), $response->lexeme->senses );
	}

}
