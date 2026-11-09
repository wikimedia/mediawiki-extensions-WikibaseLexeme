<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\PatchLexeme;

use Exception;
use Generator;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\JsonDiffLexemeJsonPatcher;
use Wikibase\Lexeme\DataAccess\Store\LexemeReadModelConverter;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\Exceptions\EditPrevented;
use Wikibase\Lexeme\Domain\Model\Exceptions\RateLimitReached;
use Wikibase\Lexeme\Domain\Model\Exceptions\ResourceTooLargeException;
use Wikibase\Lexeme\Domain\Model\Exceptions\TempAccountCreationLimitReached;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\PatchLexemeEditSummary;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemma;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\LexemeRevision;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Domain\Services\LexemeRetriever;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Lexeme\Interactors\AssertUserIsAuthorized;
use Wikibase\Lexeme\Interactors\GetLatestLexemeRevisionMetadata;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchedLexemeValidator;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexeme;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexemeRequest;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexemeValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Presentation\RestSerialization\LexemeSerializer;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Statements\Domain\Services\StatementReadModelConverter;

/**
 * @covers \Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexeme
 *
 * @license GPL-2.0-or-later
 */
class PatchLexemeTest extends MediaWikiUnitTestCase {

	public function testExecutePatchesLemmas(): void {
		$lexemeId = new LexemeId( 'L1' );
		$tags = [ 'some tag' ];
		$userComment = 'user comment';
		$readModelConverter = new LexemeReadModelConverter( $this->createStub( StatementReadModelConverter::class ) );

		$originalLexeme = new LexemeWriteModel(
			$lexemeId,
			new TermList( [ new Term( 'en', 'color' ) ] ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			nextFormId: 8,
			nextSenseId: 5,
		);
		$lexemeRetriever = $this->createStub( LexemeRetriever::class );
		$lexemeRetriever->method( 'getLexeme' )->willReturn( $readModelConverter->convert( $originalLexeme ) );
		$lexemeSerializer = $this->createStub( LexemeSerializer::class );
		$lexemeSerializer->method( 'serialize' )->willReturn( [
			'lemmas' => [ 'en' => 'color' ],
			'lexical_category' => 'Q1',
			'language' => 'Q2',
		] );

		$expectedPatchedLexeme = new LexemeWriteModel(
			$lexemeId,
			new TermList( [
				new Term( 'en', 'colour' ),
				new Term( 'en-gb', 'colour' ),
			] ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			nextFormId: 8,
			nextSenseId: 5,
		);
		$writeModelRetriever = $this->createStub( LexemeWriteModelRetriever::class );
		$writeModelRetriever->method( 'getLexemeWriteModel' )->willReturn( $originalLexeme );

		$expectedRevisionId = 123;
		$expectedLastModified = '20250101120000';
		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->once() )
			->method( 'update' )
			->with( $expectedPatchedLexeme,
				new EditMetadata( $tags, true, new PatchLexemeEditSummary( $userComment ) )
			)
			->willReturnCallback( static fn ( LexemeWriteModel $l ) => new LexemeRevision(
				$readModelConverter->convert( $l ),
				$expectedRevisionId,
				$expectedLastModified,
			) );

		$response = $this->newUseCase(
			lexemeRetriever: $lexemeRetriever,
			lexemeSerializer: $lexemeSerializer,
			lexemeWriteModelRetriever:  $writeModelRetriever,
			lexemeUpdater: $lexemeUpdater,
		)->execute( new PatchLexemeRequest(
			'L1',
			[
				[ 'op' => 'replace', 'path' => '/lemmas/en', 'value' => 'colour' ],
				[ 'op' => 'add', 'path' => '/lemmas/en-gb', 'value' => 'colour' ],
			],
			$tags,
			true,
			$userComment,
			null
		) );

		$expectedResponseLexeme = new Lexeme(
			id: $lexemeId,
			lemmas: new Lemmas(
				new Lemma( 'en', 'colour' ),
				new Lemma( 'en-gb', 'colour' ),
			),
			lexicalCategory: new ItemId( 'Q1' ),
			language: new ItemId( 'Q2' ),
			statements: new StatementList(),
			forms: new Forms(),
			senses: new Senses(),
		);

		$this->assertEquals( $expectedResponseLexeme, $response->lexeme );
		$this->assertSame( $expectedRevisionId, $response->revisionId );
		$this->assertSame( $expectedLastModified, $response->lastModified );
	}

	public function testGivenInvalidRequest_throwsWithoutUpdating(): void {
		$request = new PatchLexemeRequest( 'L1', [], [], false, null, null );
		$expectedException = $this->createStub( UseCaseError::class );

		$validator = $this->createMock( PatchLexemeValidator::class );
		$validator->expects( $this->once() )
			->method( 'validate' )
			->with( $request )
			->willThrowException( $expectedException );

		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->never() )->method( 'update' );

		try {
			( $this->newUseCase(
				lexemeUpdater: $lexemeUpdater,
				validator: $validator,
			) )->execute( $request );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	/**
	 * @dataProvider provideInapplicablePatch
	 */
	public function testGivenInapplicablePatch_throwsWithoutUpdating(
		array $patch,
		string $expectedErrorCode,
		array $expectedContext,
	): void {
		$lexemeRetriever = $this->createStub( LexemeRetriever::class );
		$lexemeRetriever->method( 'getLexeme' )
			->willReturn( $this->createStub( Lexeme::class ) );
		$lexemeSerializer = $this->createStub( LexemeSerializer::class );
		$lexemeSerializer->method( 'serialize' )->willReturn( [ 'lemmas' => [ 'en' => 'color' ] ] );

		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->never() )->method( 'update' );

		try {
			$this->newUseCase(
				lexemeRetriever: $lexemeRetriever,
				lexemeSerializer: $lexemeSerializer,
				lexemeUpdater: $lexemeUpdater,
			)->execute( new PatchLexemeRequest( 'L1', $patch, [], false, null, null ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedErrorCode, $e->errorCode );
			$this->assertSame( $expectedContext, $e->context );
		}
	}

	public static function provideInapplicablePatch(): Generator {
		yield 'non-existent path' => [
			[ [ 'op' => 'remove', 'path' => '/lemmas/de' ] ],
			UseCaseError::PATCH_TARGET_NOT_FOUND,
			[ UseCaseError::CONTEXT_PATH => '/patch/0/path' ],
		];

		yield 'non-existent from' => [
			[ [ 'op' => 'copy', 'from' => '/lemmas/de', 'path' => '/lemmas/en-gb' ] ],
			UseCaseError::PATCH_TARGET_NOT_FOUND,
			[ UseCaseError::CONTEXT_PATH => '/patch/0/from' ],
		];

		yield 'failed test operation' => [
			[
				[ 'op' => 'add', 'path' => '/lemmas/en-gb', 'value' => 'colour' ],
				[ 'op' => 'test', 'path' => '/lemmas/en', 'value' => 'colour' ],
			],
			UseCaseError::PATCH_TEST_FAILED,
			[ UseCaseError::CONTEXT_PATH => '/patch/1', UseCaseError::CONTEXT_ACTUAL_VALUE => 'color' ],
		];
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
				new PatchLexemeRequest( 'L1', [], [], true, 'user comment', null )
			);
			$this->fail( 'this should not be reached' );
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
		array $expectedContext,
	): void {
		$lexeme = new LexemeWriteModel(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'color' ) ] ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
		);
		$readModelConverter = new LexemeReadModelConverter( $this->createStub( StatementReadModelConverter::class ) );

		$lexemeRetriever = $this->createStub( LexemeRetriever::class );
		$lexemeRetriever->method( 'getLexeme' )->willReturn( $readModelConverter->convert( $lexeme ) );
		$lexemeSerializer = $this->createStub( LexemeSerializer::class );
		$lexemeSerializer->method( 'serialize' )->willReturn( [
			'lemmas' => [ 'en' => 'color' ],
			'lexical_category' => 'Q1',
			'language' => 'Q2',
		] );
		$writeModelRetriever = $this->createStub( LexemeWriteModelRetriever::class );
		$writeModelRetriever->method( 'getLexemeWriteModel' )->willReturn( $lexeme->copy() );

		$lexemeUpdater = $this->createStub( LexemeUpdater::class );
		$lexemeUpdater->method( 'update' )->willThrowException( $exception );

		try {
			$this->newUseCase(
				lexemeRetriever: $lexemeRetriever,
				lexemeSerializer: $lexemeSerializer,
				lexemeWriteModelRetriever: $writeModelRetriever,
				lexemeUpdater: $lexemeUpdater,
			)->execute( new PatchLexemeRequest(
				'L1',
				[ [ 'op' => 'replace', 'path' => '/lemmas/en', 'value' => 'colour' ] ],
				[],
				false,
				null,
				null,
			) );

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
			[
				UseCaseError::CONTEXT_DENIAL_REASON => 'spamblacklist',
				UseCaseError::CONTEXT_DENIAL_CONTEXT => [ 'spamblacklist' => [ 'matches' => [ $blockedText ] ] ],
			],
		];
	}

	private function newUseCase(
		?LexemeRetriever $lexemeRetriever = null,
		?LexemeSerializer $lexemeSerializer = null,
		?LexemeWriteModelRetriever $lexemeWriteModelRetriever = null,
		?LexemeUpdater $lexemeUpdater = null,
		?PatchLexemeValidator $validator = null,
		?AssertUserIsAuthorized $assertUserIsAuthorized = null,
	): PatchLexeme {
		if ( $validator === null ) {
			$validator = $this->createStub( PatchLexemeValidator::class );
			$validator->method( 'getValidatedLexemeId' )
				->willReturn( new LexemeId( 'L1' ) );
		}
		return new PatchLexeme(
			$this->createStub( GetLatestLexemeRevisionMetadata::class ),
			$lexemeRetriever ?? $this->createStub( LexemeRetriever::class ),
			$lexemeSerializer ?? $this->createStub( LexemeSerializer::class ),
			new JsonDiffLexemeJsonPatcher(),
			$lexemeWriteModelRetriever ?? $this->createStub( LexemeWriteModelRetriever::class ),
			$lexemeUpdater,
			$validator,
			$assertUserIsAuthorized ?? $this->createStub( AssertUserIsAuthorized::class ),
			new PatchedLexemeValidator(),
		);
	}

}
