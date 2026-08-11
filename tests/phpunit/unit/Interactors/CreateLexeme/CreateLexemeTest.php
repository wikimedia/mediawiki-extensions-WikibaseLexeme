<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\CreateLexeme;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemma;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Domain\Services\LexemeCreator;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexeme;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeRequest;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexeme
 *
 * @license GPL-2.0-or-later
 */
class CreateLexemeTest extends MediaWikiUnitTestCase {

	public function testExecuteCreatesLexeme(): void {
		$enLemma = 'potato';
		$lexicalCategory = 'Q1';
		$language = 'Q2';
		$expectedLexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new Lemmas( new Lemma( 'en', $enLemma ) ),
			new ItemId( $lexicalCategory ),
			new ItemId( $language ),
			new StatementList(),
			new Forms(),
			new Senses(),
		);

		$lexemeCreator = $this->createMock( LexemeCreator::class );
		$lexemeCreator->expects( $this->once() )
			->method( 'create' )
			->with( new LexemeWriteModel(
				null,
				new TermList( [ new Term( 'en', $enLemma ) ] ),
				new ItemId( $lexicalCategory ),
				new ItemId( $language ),
			) )
			->willReturn( $expectedLexeme );

		$response = ( new CreateLexeme( $lexemeCreator ) )->execute( new CreateLexemeRequest( [
			'lemmas' => [ 'en' => $enLemma ],
			'lexical_category' => $lexicalCategory,
			'language' => $language,
		] ) );

		$this->assertSame( $expectedLexeme, $response->lexeme );
	}

}
