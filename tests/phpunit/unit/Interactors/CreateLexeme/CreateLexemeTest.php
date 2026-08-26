<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\CreateLexeme;

use Exception;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\EditSummaryAction;
use Wikibase\Lexeme\Domain\Model\Exceptions\ResourceTooLargeException;
use Wikibase\Lexeme\Domain\Model\Exceptions\TempAccountCreationLimitReached;
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

		$request = new CreateLexemeRequest(
			[
				'lemmas' => [ 'en' => $enLemma ],
				'lexical_category' => $lexicalCategory,
				'language' => $language,
			],
			[ 'some tag' ],
			true,
			'user comment',
		);
		$deserializedLexeme = new LexemeWriteModel(
			null,
			new TermList( [ new Term( 'en', $enLemma ) ] ),
			new ItemId( $lexicalCategory ),
			new ItemId( $language ),
		);
		$editMetadata = new EditMetadata( [ 'some tag' ], true, 'user comment', EditSummaryAction::CREATE_LEXEME );

		$validator = $this->createMock( CreateLexemeValidator::class );
		$validator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( $request );
		$validator->method( 'getValidatedLexeme' )
			->willReturn( $deserializedLexeme );
		$validator->method( 'getValidatedEditMetadata' )
			->willReturn( $editMetadata );

		$lexemeCreator = $this->createMock( LexemeCreator::class );
		$lexemeCreator->expects( $this->once() )
			->method( 'create' )
			->with( $deserializedLexeme, $editMetadata )
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
			->execute( new CreateLexemeRequest( [], [], false, null ) );
	}

	/**
	 * @dataProvider exceptionProvider
	 */
	public function testGivenLexemeCreatorException_throwsUseCaseError(
		Exception $exception,
		string $expectedErrorCode,
		string $expectedErrorMessage,
		array $expectedContext
	): void {
		$lexemeCreator = $this->createStub( LexemeCreator::class );
		$lexemeCreator->method( 'create' )->willThrowException( $exception );

		$validator = $this->createStub( CreateLexemeValidator::class );

		try {
			( new CreateLexeme( $lexemeCreator, $validator ) )
				->execute( new CreateLexemeRequest( [], [], false, null ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedErrorCode, $e->errorCode );
			$this->assertSame( $expectedErrorMessage, $e->errorMessage );
			$this->assertSame( $expectedContext, $e->context );
		}
	}

	public static function exceptionProvider(): iterable {
		yield 'rate limit reached' => [
			new TempAccountCreationLimitReached(),
			UseCaseError::REQUEST_LIMIT_REACHED,
			'Exceeded the limit of actions that can be performed in a given span of time',
			[
				UseCaseError::CONTEXT_REASON =>
					UseCaseError::REQUEST_LIMIT_REASON_TEMP_ACCOUNT_CREATION_LIMIT,
			],
		];

		$limit = 1024;
		yield 'resource too large' => [
			new ResourceTooLargeException( $limit ),
			UseCaseError::RESOURCE_TOO_LARGE,
			"Edit resulted in a resource that exceeds the size limit of $limit kB",
			[ UseCaseError::CONTEXT_LIMIT => $limit ],
		];
	}

}
