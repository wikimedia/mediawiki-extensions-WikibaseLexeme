<?php

declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataAccess\Store\EntityRevisionLookupLexemeRetriever;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\EntityRevisionLookupLexemeRetriever
 *
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupLexemeRetrieverTest extends TestCase {

	public function testGetLexeme(): void {
		$lexemeId = new LexemeId( 'L123' );
		$lexeme = NewLexeme::havingId( $lexemeId )->build();

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $lexemeId )
			->willReturn( new EntityRevision( $lexeme ) );

		$retriever = new EntityRevisionLookupLexemeRetriever( $entityRevisionLookup );

		$this->assertEquals( new Lexeme( $lexemeId ), $retriever->getLexeme( $lexemeId ) );
	}

	public function testGivenLexemeDoesNotExist_getLexemeReturnsNull(): void {
		$lexemeId = new LexemeId( 'L321' );

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $lexemeId )
			->willReturn( null );

		$retriever = new EntityRevisionLookupLexemeRetriever( $entityRevisionLookup );

		$this->assertNull( $retriever->getLexeme( $lexemeId ) );
	}

	public function testGivenLexemeRedirected_getLexemeReturnsNull(): void {
		$lexemeId = new LexemeId( 'L321' );

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $lexemeId )
			->willThrowException( $this->createStub( RevisionedUnresolvedRedirectException::class ) );

		$retriever = new EntityRevisionLookupLexemeRetriever( $entityRevisionLookup );

		$this->assertNull( $retriever->getLexeme( $lexemeId ) );
	}

}
