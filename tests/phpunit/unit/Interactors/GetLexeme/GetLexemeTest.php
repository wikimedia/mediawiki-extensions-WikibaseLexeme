<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\GetLexeme;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\LatestLexemeRevisionMetadataResult;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemma;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Domain\Services\LexemeRetriever;
use Wikibase\Lexeme\Domain\Services\LexemeRevisionMetadataRetriever;
use Wikibase\Lexeme\Interactors\GetLexeme\GetLexeme;
use Wikibase\Lexeme\Interactors\GetLexeme\GetLexemeRequest;
use Wikibase\Lexeme\Interactors\GetLexeme\GetLexemeValidator;
use Wikibase\Lexeme\Interactors\GetLexeme\LexemeRedirect;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Lexeme\Interactors\GetLexeme\GetLexeme
 *
 * @license GPL-2.0-or-later
 */
class GetLexemeTest extends MediaWikiUnitTestCase {

	public function testExecuteRetrievesLexeme(): void {
		$lexemeId = new LexemeId( 'L123' );
		$expectedLexeme = new Lexeme(
			$lexemeId,
			new Lemmas(
				new Lemma( 'en-ca', 'colour' ),
				new Lemma( 'en-us', 'color' ),
			),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			new StatementList(),
			new Forms(),
			new Senses(),
		);
		$lastModifiedTimestamp = '20261111070707';
		$revisionId = 42;

		$lexemeRetriever = $this->createMock( LexemeRetriever::class );
		$lexemeRetriever->expects( $this->once() )
			->method( 'getLexeme' )
			->with( $lexemeId )
			->willReturn( $expectedLexeme );

		$metadataRetriever = $this->createStub( LexemeRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestLexemeRevisionMetadataResult::concreteRevision( $revisionId, $lastModifiedTimestamp ) );

		$response = ( new GetLexeme( $lexemeRetriever, $metadataRetriever, new GetLexemeValidator() ) )
			->execute( new GetLexemeRequest( "$lexemeId" ) );

		$this->assertSame( $expectedLexeme, $response->lexeme );
		$this->assertSame( $revisionId, $response->revisionId );
		$this->assertSame( $lastModifiedTimestamp, $response->lastModified );
	}

	public function testGivenLexemeIsRedirect_executeThrowsLexemeRedirect(): void {
		$redirectTarget = new LexemeId( 'L456' );

		$lexemeRetriever = $this->createMock( LexemeRetriever::class );
		$lexemeRetriever->expects( $this->never() )
			->method( 'getLexeme' );

		$metadataRetriever = $this->createStub( LexemeRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestLexemeRevisionMetadataResult::redirect( $redirectTarget ) );

		try {
			( new GetLexeme( $lexemeRetriever, $metadataRetriever, new GetLexemeValidator() ) )
				->execute( new GetLexemeRequest( 'L123' ) );
			$this->fail( 'Expected LexemeRedirect to be thrown' );
		} catch ( LexemeRedirect $e ) {
			$this->assertSame( $redirectTarget, $e->redirectTarget );
		}
	}

	public function testGivenInvalidLexemeId_executeThrowsUseCaseError(): void {
		$lexemeRetriever = $this->createMock( LexemeRetriever::class );
		$lexemeRetriever->expects( $this->never() )
			->method( 'getLexeme' );

		$metadataRetriever = $this->createMock( LexemeRevisionMetadataRetriever::class );
		$metadataRetriever->expects( $this->never() )
			->method( 'getLatestRevisionMetadata' );

		try {
			( new GetLexeme( $lexemeRetriever, $metadataRetriever, new GetLexemeValidator() ) )
				->execute( new GetLexemeRequest( 'not-a-lexeme-id' ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->errorCode );
			$this->assertSame( "Invalid path parameter: 'lexeme_id'", $e->errorMessage );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'lexeme_id' ], $e->context );
		}
	}

	public function testGivenLexemeNotFound_executeThrowsUseCaseError(): void {
		$lexemeRetriever = $this->createMock( LexemeRetriever::class );
		$lexemeRetriever->expects( $this->never() )
			->method( 'getLexeme' );

		$metadataRetriever = $this->createStub( LexemeRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestLexemeRevisionMetadataResult::lexemeNotFound() );

		try {
			( new GetLexeme( $lexemeRetriever, $metadataRetriever, new GetLexemeValidator() ) )
				->execute( new GetLexemeRequest( 'L123' ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::LEXEME_NOT_FOUND, $e->errorCode );
			$this->assertEquals( 'The requested lexeme does not exist', $e->errorMessage );
		}
	}

}
