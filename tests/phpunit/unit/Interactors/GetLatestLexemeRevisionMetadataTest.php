<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\LatestLexemeRevisionMetadataResult;
use Wikibase\Lexeme\Domain\Services\LexemeRevisionMetadataRetriever;
use Wikibase\Lexeme\Interactors\GetLatestLexemeRevisionMetadata;
use Wikibase\Lexeme\Interactors\GetLexeme\LexemeRedirect;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @covers \Wikibase\Lexeme\Interactors\GetLatestLexemeRevisionMetadata
 *
 * @license GPL-2.0-or-later
 */
class GetLatestLexemeRevisionMetadataTest extends TestCase {

	public function testExecute(): void {
		$lexemeId = new LexemeId( 'L123' );
		$expectedRevisionId = 123;
		$expectedLastModified = '20220101001122';

		$metadataRetriever = $this->createMock( LexemeRevisionMetadataRetriever::class );
		$metadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $lexemeId )
			->willReturn( LatestLexemeRevisionMetadataResult::concreteRevision(
				$expectedRevisionId,
				$expectedLastModified
			) );

		[ $revId, $lastModified ] = ( new GetLatestLexemeRevisionMetadata( $metadataRetriever ) )->execute( $lexemeId );

		$this->assertSame( $expectedRevisionId, $revId );
		$this->assertSame( $expectedLastModified, $lastModified );
	}

	public function testGivenLexemeDoesNotExist_throwsUseCaseError(): void {
		$lexemeId = new LexemeId( 'L123' );

		$metadataRetriever = $this->createStub( LexemeRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestLexemeRevisionMetadataResult::lexemeNotFound() );

		try {
			( new GetLatestLexemeRevisionMetadata( $metadataRetriever ) )->execute( $lexemeId );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::RESOURCE_NOT_FOUND, $e->errorCode );
			$this->assertSame( 'The requested resource does not exist', $e->errorMessage );
			$this->assertSame( [ 'resource_type' => Lexeme::ENTITY_TYPE ], $e->context );
		}
	}

	public function testGivenLexemeRedirect_throwsLexemeRedirect(): void {
		$redirectSource = new LexemeId( 'L321' );
		$redirectTarget = new LexemeId( 'L123' );

		$metadataRetriever = $this->createStub( LexemeRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestLexemeRevisionMetadataResult::redirect( $redirectTarget ) );

		try {
			( new GetLatestLexemeRevisionMetadata( $metadataRetriever ) )->execute( $redirectSource );
			$this->fail( 'this should not be reached' );
		} catch ( LexemeRedirect $e ) {
			$this->assertSame( $redirectTarget, $e->redirectTarget );
		}
	}
}
