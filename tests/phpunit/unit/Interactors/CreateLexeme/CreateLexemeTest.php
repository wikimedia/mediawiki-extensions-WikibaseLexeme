<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\CreateLexeme;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\EditSummaryAction;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemma;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\LexemeRevision;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Domain\Services\LexemeCreator;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexeme;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeRequest;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
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
		$expectedRevisionId = 123;
		$expectedLastModified = '20250101120000';
		$expectedLexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new Lemmas( new Lemma( 'en', $enLemma ) ),
			new ItemId( $lexicalCategory ),
			new ItemId( $language ),
			new StatementList(),
			new Forms(),
			new Senses(),
		);

		$request = new CreateLexemeRequest( [
			'lemmas' => [ 'en' => $enLemma ],
			'lexical_category' => $lexicalCategory,
			'language' => $language,
		] );
		$deserializedLexeme = new LexemeWriteModel(
			null,
			new TermList( [ new Term( 'en', $enLemma ) ] ),
			new ItemId( $lexicalCategory ),
			new ItemId( $language ),
		);

		$validator = $this->createMock( CreateLexemeValidator::class );
		$validator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( $request );
		$validator->method( 'getValidatedLexeme' )
			->willReturn( $deserializedLexeme );

		$lexemeCreator = $this->createMock( LexemeCreator::class );
		$lexemeCreator->expects( $this->once() )
			->method( 'create' )
			->with( $deserializedLexeme, new EditMetadata( EditSummaryAction::CREATE_LEXEME ) )
			->willReturn( new LexemeRevision( $expectedLexeme, $expectedRevisionId, $expectedLastModified ) );

		$response = ( new CreateLexeme( $lexemeCreator, $validator ) )->execute( $request );

		$this->assertSame( $expectedLexeme, $response->lexeme );
		$this->assertSame( $expectedRevisionId, $response->revisionId );
		$this->assertSame( $expectedLastModified, $response->lastModified );
	}

	public function testGivenInvalidRequest_throwsWithoutCreating(): void {
		$lexemeCreator = $this->createMock( LexemeCreator::class );
		$lexemeCreator->expects( $this->never() )->method( 'create' );

		$validator = $this->createStub( CreateLexemeValidator::class );
		$validator->method( 'validateAndDeserialize' )
			->willThrowException( UseCaseError::newMissingField( '/lexeme', 'lemmas' ) );

		$this->expectException( UseCaseError::class );

		( new CreateLexeme( $lexemeCreator, $validator ) )
			->execute( new CreateLexemeRequest( [] ) );
	}

}
