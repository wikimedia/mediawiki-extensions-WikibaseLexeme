<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\AddLexemeStatement;

use Exception;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\AddStatementEditSummary;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\Exceptions\EditPrevented;
use Wikibase\Lexeme\Domain\Model\Exceptions\RateLimitReached;
use Wikibase\Lexeme\Domain\Model\Exceptions\ResourceTooLargeException;
use Wikibase\Lexeme\Domain\Model\Exceptions\TempAccountCreationLimitReached;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\LatestLexemeRevisionMetadataResult;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\LexemeRevision;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Domain\Services\LexemeRevisionMetadataRetriever;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatement;
use Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatementRequest;
use Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatementValidator;
use Wikibase\Lexeme\Interactors\AssertUserIsAuthorized;
use Wikibase\Lexeme\Interactors\GetLexeme\LexemeRedirect;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\Statement as ReadModelStatement;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatement
 *
 * @license GPL-2.0-or-later
 */
class AddLexemeStatementTest extends MediaWikiUnitTestCase {

	public function testExecuteAddsStatement(): void {
		$lexemeId = new LexemeId( 'L1' );
		$statementId = new StatementGuid( $lexemeId, 'some-guid' );
		$statement = new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P123' ) ) );
		$request = new AddLexemeStatementRequest(
			'L1',
			[ 'property' => [ 'id' => 'P123' ], 'value' => [ 'type' => 'novalue' ] ],
			[ 'some tag' ],
			true,
			'user comment',
			null,
		);
		$expectedRevisionId = 123;
		$expectedLastModified = '20250101120000';
		$expectedStatement = $this->createStub( ReadModelStatement::class );
		$expectedStatement->method( 'getGuid' )->willReturn( $statementId );

		$validator = $this->createMock( AddLexemeStatementValidator::class );
		$validator->expects( $this->once() )->method( 'validate' )->with( $request );
		$validator->method( 'getValidatedLexemeId' )->willReturn( $lexemeId );
		$validator->method( 'getValidatedStatement' )->willReturn( $statement );

		$guidGenerator = $this->createStub( GuidGenerator::class );
		$guidGenerator->method( 'newStatementId' )->willReturn( $statementId );

		$lexeme = new LexemeWriteModel( $lexemeId, new TermList(), new ItemId( 'Q1' ), new ItemId( 'Q2' ) );
		$lexemeRetriever = $this->createStub( LexemeWriteModelRetriever::class );
		$lexemeRetriever->method( 'getLexemeWriteModel' )->willReturn( $lexeme );

		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->callback( static fn ( LexemeWriteModel $l ) => $l->getStatements()->getFirstStatementWithGuid(
					(string)$statementId
				) === $statement ),
				new EditMetadata( [ 'some tag' ], true, new AddStatementEditSummary( 'user comment', $statement ) ),
			)
			->willReturn( new LexemeRevision(
				new Lexeme(
					$lexemeId,
					new Lemmas(),
					new ItemId( 'Q1' ),
					new ItemId( 'Q2' ),
					new StatementList( $expectedStatement ),
					new Forms(),
					new Senses(),
				),
				$expectedRevisionId,
				$expectedLastModified,
			) );

		$metadataRetriever = $this->createStub(
			LexemeRevisionMetadataRetriever::class
		);
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn(
				LatestLexemeRevisionMetadataResult::concreteRevision(
					1,
					'20260910070707',
				)
			);
		$response = $this->newUseCase(
			metadataRetriever: $metadataRetriever,
			lexemeRetriever: $lexemeRetriever,
			lexemeUpdater: $lexemeUpdater,
			guidGenerator: $guidGenerator,
			validator: $validator,
		)->execute( $request );

		$this->assertSame( $expectedStatement, $response->statement );
		$this->assertSame( $expectedRevisionId, $response->revisionId );
		$this->assertSame( $expectedLastModified, $response->lastModified );
	}

	public function testGivenInvalidRequest_throwsWithoutUpdating(): void {
		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->never() )->method( 'update' );

		$validator = $this->createStub( AddLexemeStatementValidator::class );
		$validator->method( 'validate' )
			->willThrowException( UseCaseError::newInvalidPathParameter( 'lexeme_id' ) );

		$this->expectException( UseCaseError::class );

		$this->newUseCase(
			lexemeUpdater: $lexemeUpdater,
			validator: $validator,
		)->execute(
			new AddLexemeStatementRequest( 'X', [], [], false, null, null )
		);
	}

	public function testGivenUnauthorizedUser_throwsWithoutUpdating(): void {
		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->never() )->method( 'update' );

		$expectedException = UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_USER_BLOCKED );
		$assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$assertUserIsAuthorized->method( 'checkEditPermissions' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase(
				lexemeUpdater: $lexemeUpdater,
				assertUserIsAuthorized: $assertUserIsAuthorized,
			)->execute(
				new AddLexemeStatementRequest( 'L1', [], [], true, 'user comment', null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenLexemeIsRedirect_throws(): void {
		$redirectTarget = new LexemeId( 'L456' );

		$metadataRetriever = $this->createStub(
			LexemeRevisionMetadataRetriever::class
		);
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn(
				LatestLexemeRevisionMetadataResult::redirect( $redirectTarget )
			);

		$lexemeRetriever = $this->createMock(
			LexemeWriteModelRetriever::class
		);
		$lexemeRetriever->expects( $this->never() )
			->method( 'getLexemeWriteModel' );

		try {
			$this->newUseCase(
				metadataRetriever: $metadataRetriever,
				lexemeRetriever: $lexemeRetriever,
			)->execute(
				new AddLexemeStatementRequest(
					'L123',
					[],
					[],
					false,
					null,
					null,
				)
			);
			$this->fail( 'Expected LexemeRedirect to be thrown' );
		} catch ( LexemeRedirect $e ) {
			$this->assertSame(
				$redirectTarget,
				$e->redirectTarget
			);
		}
	}

	public function testGivenLexemeNotFound_throws(): void {
		$metadataRetriever = $this->createStub(
			LexemeRevisionMetadataRetriever::class
		);
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestLexemeRevisionMetadataResult::lexemeNotFound() );

		$lexemeRetriever = $this->createMock(
			LexemeWriteModelRetriever::class
		);
		$lexemeRetriever->expects( $this->never() )
			->method( 'getLexemeWriteModel' );

		try {
			$this->newUseCase(
				metadataRetriever: $metadataRetriever,
				lexemeRetriever: $lexemeRetriever,
			)->execute(
				new AddLexemeStatementRequest(
					'L123',
					[],
					[],
					false,
					null,
					null,
				)
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame(
				UseCaseError::RESOURCE_NOT_FOUND,
				$e->errorCode
			);
			$this->assertSame(
				'The requested resource does not exist',
				$e->errorMessage
			);
			$this->assertSame(
				[ UseCaseError::CONTEXT_RESOURCE_TYPE => 'lexeme' ],
				$e->context
			);
		}
	}

	/**
	 * @dataProvider exceptionProvider
	 */
	public function testGivenLexemeUpdaterException_throwsUseCaseError(
		Exception $exception,
		string $expectedErrorCode,
		string $expectedErrorMessage,
		array $expectedContext
	): void {
		$lexemeId = new LexemeId( 'L1' );
		$statementId = new StatementGuid( $lexemeId, 'some-guid' );
		$statement = new Statement(
			new PropertyNoValueSnak( new NumericPropertyId( 'P123' ) )
		);

		$request = new AddLexemeStatementRequest(
			'L1',
			[],
			[],
			false,
			null,
			null
		);

		$validator = $this->createStub( AddLexemeStatementValidator::class );
		$validator->method( 'getValidatedLexemeId' )->willReturn( $lexemeId );
		$validator->method( 'getValidatedStatement' )->willReturn( $statement );

		$guidGenerator = $this->createStub( GuidGenerator::class );
		$guidGenerator->method( 'newStatementId' )->willReturn( $statementId );

		$lexeme = new LexemeWriteModel(
			$lexemeId,
			new TermList(),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
		);

		$lexemeRetriever = $this->createStub( LexemeWriteModelRetriever::class );
		$lexemeRetriever->method( 'getLexemeWriteModel' )->willReturn( $lexeme );

		$lexemeUpdater = $this->createStub( LexemeUpdater::class );
		$lexemeUpdater->method( 'update' )->willThrowException( $exception );

		$metadataRetriever = $this->createStub(
			LexemeRevisionMetadataRetriever::class
		);
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn(
				LatestLexemeRevisionMetadataResult::concreteRevision(
					1,
					'20260910070707',
				)
			);

		try {
			$this->newUseCase(
				metadataRetriever: $metadataRetriever,
				lexemeRetriever: $lexemeRetriever,
				lexemeUpdater: $lexemeUpdater,
				guidGenerator: $guidGenerator,
				validator: $validator,
			)->execute( $request );

			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedErrorCode, $e->errorCode );
			$this->assertSame( $expectedErrorMessage, $e->errorMessage );
			$this->assertSame( $expectedContext, $e->context );
		}
	}

	public static function exceptionProvider(): iterable {
		yield 'rate limit reached' => [
			new RateLimitReached(),
			UseCaseError::REQUEST_LIMIT_REACHED,
			'Exceeded the limit of actions that can be performed in a given span of time',
			[ UseCaseError::CONTEXT_REASON => UseCaseError::REQUEST_LIMIT_REASON_RATE_LIMIT ],
		];

		yield 'temp account creation limit reached' => [
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

		$blockedText = 'example.com';
		yield 'edit prevented' => [
			new EditPrevented( 'spamblacklist', [ 'spamblacklist' => [ 'matches' => [ $blockedText ] ] ] ),
			UseCaseError::PERMISSION_DENIED,
			'Access to resource is denied',
			[ UseCaseError::CONTEXT_DENIAL_REASON => 'spamblacklist',
				UseCaseError::CONTEXT_DENIAL_CONTEXT => [ 'spamblacklist' => [ 'matches' => [ $blockedText ] ] ] ],
		];
	}

	private function newUseCase(
		?LexemeRevisionMetadataRetriever $metadataRetriever = null,
		?LexemeWriteModelRetriever $lexemeRetriever = null,
		?LexemeUpdater $lexemeUpdater = null,
		?GuidGenerator $guidGenerator = null,
		?AddLexemeStatementValidator $validator = null,
		?AssertUserIsAuthorized $assertUserIsAuthorized = null,
	): AddLexemeStatement {
		if ( $metadataRetriever === null ) {
			$metadataRetriever = $this->createStub( LexemeRevisionMetadataRetriever::class );
			$metadataRetriever->method( 'getLatestRevisionMetadata' )
				->willReturn( LatestLexemeRevisionMetadataResult::concreteRevision( 1, '20260910070707' ) );
		}

		if ( $validator === null ) {
			$validator = $this->createStub( AddLexemeStatementValidator::class );
			$validator->method( 'getValidatedLexemeId' )
				->willReturn( new LexemeId( 'L123' ) );
		}
		return new AddLexemeStatement(
			$lexemeRetriever ?? $this->createStub( LexemeWriteModelRetriever::class ),
			$lexemeUpdater ?? $this->createStub( LexemeUpdater::class ),
			$guidGenerator ?? $this->createStub( GuidGenerator::class ),
			$validator,
			$metadataRetriever,
			$assertUserIsAuthorized ?? $this->createStub( AssertUserIsAuthorized::class ),
		);
	}
}
