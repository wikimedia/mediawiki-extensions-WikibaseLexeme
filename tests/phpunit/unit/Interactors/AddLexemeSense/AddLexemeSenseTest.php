<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\AddLexemeSense;

use Exception;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\Store\LexemeReadModelConverter;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\Model\AddSenseEditSummary;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\Exceptions\EditPrevented;
use Wikibase\Lexeme\Domain\Model\Exceptions\RateLimitReached;
use Wikibase\Lexeme\Domain\Model\Exceptions\ResourceTooLargeException;
use Wikibase\Lexeme\Domain\Model\Exceptions\TempAccountCreationLimitReached;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\LatestLexemeRevisionMetadataResult;
use Wikibase\Lexeme\Domain\Model\ReadModel\LexemeRevision;
use Wikibase\Lexeme\Domain\Model\User;
use Wikibase\Lexeme\Domain\Services\LexemeRevisionMetadataRetriever;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSense;
use Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSenseRequest;
use Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSenseValidator;
use Wikibase\Lexeme\Interactors\AssertUserIsAuthorized;
use Wikibase\Lexeme\Interactors\GetLexeme\LexemeRedirect;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Repo\Domains\Statements\Domain\Services\StatementReadModelConverter;

/**
 * @covers \Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSense
 *
 * @license GPL-2.0-or-later
 */
class AddLexemeSenseTest extends MediaWikiUnitTestCase {

	public function testExecuteAddsSense(): void {
		$lexemeId = new LexemeId( 'L1' );
		$gloss = 'a starchy tuber';
		$tags = [ 'some tag' ];
		$userComment = 'user comment';
		$request = new AddLexemeSenseRequest(
			'L1',
			[ 'glosses' => [ 'en' => $gloss ] ],
			$tags,
			true,
			$userComment,
			null,
		);

		$sense = new BlankSense();
		$sense->getGlosses()->setTerm( new Term( 'en', $gloss ) );
		$validator = $this->createStub( AddLexemeSenseValidator::class );
		$validator->method( 'getValidatedLexemeId' )->willReturn( $lexemeId );
		$validator->method( 'getValidatedSense' )->willReturn( $sense );

		$lexeme = new LexemeWriteModel( $lexemeId, new TermList(), new ItemId( 'Q1' ), new ItemId( 'Q2' ) );
		$lexemeRetriever = $this->createStub( LexemeWriteModelRetriever::class );
		$lexemeRetriever->method( 'getLexemeWriteModel' )->willReturn( $lexeme );

		$expectedRevisionId = 123;
		$expectedLastModified = '20250101120000';
		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->once() )
			->method( 'update' )
			->with( $lexeme, new EditMetadata( $tags, true, new AddSenseEditSummary( $userComment, $sense ) ) )
			->willReturnCallback( fn ( LexemeWriteModel $l ) => new LexemeRevision(
				( new LexemeReadModelConverter( $this->createStub( StatementReadModelConverter::class ) ) )
					->convert( $l ),
				$expectedRevisionId,
				$expectedLastModified,
			) );

		$response = $this->newUseCase(
			lexemeRetriever: $lexemeRetriever,
			lexemeUpdater: $lexemeUpdater,
			validator: $validator,
		)->execute( $request );

		$this->assertSame( "{$lexemeId}-S1", $response->sense->id->getSerialization() );
		$this->assertSame( $gloss, $response->sense->glosses['en']->text );
		$this->assertSame( $expectedRevisionId, $response->revisionId );
		$this->assertSame( $expectedLastModified, $response->lastModified );
	}

	public function testGivenInvalidRequest_throwsWithoutUpdating(): void {
		$request = new AddLexemeSenseRequest( 'L1', [], [], false, 'user comment', null );
		$expectedException = $this->createStub( UseCaseError::class );

		$validator = $this->createMock( AddLexemeSenseValidator::class );
		$validator->expects( $this->once() )
			->method( 'validate' )
			->with( $request )
			->willThrowException( $expectedException );

		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->never() )->method( 'update' );

		try {
			$this->newUseCase( lexemeUpdater: $lexemeUpdater, validator: $validator )
				->execute( $request );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
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
		$lexemeRetriever = $this->createStub( LexemeWriteModelRetriever::class );
		$lexemeRetriever->method( 'getLexemeWriteModel' )
			->willReturn( new LexemeWriteModel( new LexemeId( 'L1' ) ) );

		$lexemeUpdater = $this->createStub( LexemeUpdater::class );
		$lexemeUpdater->method( 'update' )->willThrowException( $exception );

		$metadataRetriever = $this->createStub( LexemeRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn(
				LatestLexemeRevisionMetadataResult::concreteRevision( 1, '20260910070707' )
			);

		try {
			$this->newUseCase(
				lexemeRetriever: $lexemeRetriever,
				lexemeUpdater: $lexemeUpdater,
				metadataRetriever: $metadataRetriever,
			)->execute( new AddLexemeSenseRequest( 'L1', [], [], false, null, null ) );

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

	public function testGivenUnauthorizedUser_throwsWithoutUpdating(): void {
		$lexemeId = new LexemeId( 'L1' );
		$username = 'SomeUser';
		$validator = $this->createStub( AddLexemeSenseValidator::class );
		$validator->method( 'getValidatedLexemeId' )->willReturn( $lexemeId );

		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->never() )->method( 'update' );

		$expectedException = UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_USER_BLOCKED );
		$assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$assertUserIsAuthorized->expects( $this->once() )
			->method( 'checkEditPermissions' )
			->with( User::withUsername( $username ), $lexemeId )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase(
				lexemeUpdater: $lexemeUpdater,
				validator: $validator,
				assertUserIsAuthorized: $assertUserIsAuthorized,
			)->execute(
				new AddLexemeSenseRequest( 'L1', [], [], false, 'user comment', $username )
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenLexemeNotFound_throwsWithoutUpdating(): void {
		$validator = $this->createStub( AddLexemeSenseValidator::class );
		$validator->method( 'getValidatedLexemeId' )->willReturn( new LexemeId( 'L999' ) );

		$metadataRetriever = $this->createStub( LexemeRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestLexemeRevisionMetadataResult::lexemeNotFound() );

		$assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$assertUserIsAuthorized->expects( $this->never() )->method( 'checkEditPermissions' );

		$lexemeRetriever = $this->createMock( LexemeWriteModelRetriever::class );
		$lexemeRetriever->expects( $this->never() )->method( 'getLexemeWriteModel' );

		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->never() )->method( 'update' );

		try {
			$this->newUseCase(
				lexemeRetriever: $lexemeRetriever,
				lexemeUpdater: $lexemeUpdater,
				validator: $validator,
				metadataRetriever: $metadataRetriever,
				assertUserIsAuthorized: $assertUserIsAuthorized,
			)->execute( new AddLexemeSenseRequest( 'L999', [], [], false, null, null ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::RESOURCE_NOT_FOUND, $e->errorCode );
			$this->assertSame( [ UseCaseError::CONTEXT_RESOURCE_TYPE => 'lexeme' ], $e->context );
		}
	}

	public function testGivenLexemeIsRedirect_throwsWithoutUpdating(): void {
		$redirectTarget = new LexemeId( 'L456' );

		$metadataRetriever = $this->createStub( LexemeRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestLexemeRevisionMetadataResult::redirect( $redirectTarget ) );

		$assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$assertUserIsAuthorized->expects( $this->never() )->method( 'checkEditPermissions' );

		$lexemeRetriever = $this->createMock( LexemeWriteModelRetriever::class );
		$lexemeRetriever->expects( $this->never() )->method( 'getLexemeWriteModel' );

		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->never() )->method( 'update' );

		try {
			$this->newUseCase(
				lexemeRetriever: $lexemeRetriever,
				lexemeUpdater: $lexemeUpdater,
				metadataRetriever: $metadataRetriever,
				assertUserIsAuthorized: $assertUserIsAuthorized,
			)->execute( new AddLexemeSenseRequest( 'L1', [], [], false, null, null ) );
			$this->fail( 'Expected LexemeRedirect to be thrown' );
		} catch ( LexemeRedirect $e ) {
			$this->assertSame( $redirectTarget, $e->redirectTarget );
		}
	}

	private function newUseCase(
		?LexemeWriteModelRetriever $lexemeRetriever = null,
		?LexemeUpdater $lexemeUpdater = null,
		?AddLexemeSenseValidator $validator = null,
		?LexemeRevisionMetadataRetriever $metadataRetriever = null,
		?AssertUserIsAuthorized $assertUserIsAuthorized = null,
	): AddLexemeSense {
		if ( $metadataRetriever === null ) {
			$metadataRetriever = $this->createStub( LexemeRevisionMetadataRetriever::class );
			$metadataRetriever->method( 'getLatestRevisionMetadata' )
				->willReturn( LatestLexemeRevisionMetadataResult::concreteRevision( 1, '20260925070707' ) );
		}

		if ( $validator === null ) {
			$validator = $this->createStub( AddLexemeSenseValidator::class );
			$validator->method( 'getValidatedLexemeId' )->willReturn( new LexemeId( 'L1' ) );
			$validator->method( 'getValidatedSense' )->willReturn( new BlankSense() );
		}

		return new AddLexemeSense(
			$lexemeRetriever ?? $this->createStub( LexemeWriteModelRetriever::class ),
			$lexemeUpdater ?? $this->createStub( LexemeUpdater::class ),
			$validator,
			$metadataRetriever,
			$assertUserIsAuthorized ?? $this->createStub( AssertUserIsAuthorized::class ),
		);
	}

}
