<?php

namespace Wikibase\Lexeme\Tests\Unit\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataAccess\Store\EntityRevisionLookupLexemeRevisionMetadataRetriever;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\EntityRevisionLookupLexemeRevisionMetadataRetriever
 *
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupLexemeRevisionMetadataRetrieverTest extends TestCase {

	public function testGivenConcreteRevision_returnsMetadata(): void {
		$lexemeId = new LexemeId( 'L1234' );
		$expectedRevisionId = 42;
		$expectedRevisionTimestamp = '20260720070707';
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $lexemeId )
			->willReturn( LatestRevisionIdResult::concreteRevision( $expectedRevisionId, $expectedRevisionTimestamp ) );

		$metaDataRetriever = new EntityRevisionLookupLexemeRevisionMetadataRetriever( $entityRevisionLookup );
		$result = $metaDataRetriever->getLatestRevisionMetadata( $lexemeId );

		$this->assertSame( $expectedRevisionId, $result->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $result->getRevisionTimestamp() );
	}

	public function testGivenRedirect_returnsRedirectMetadata(): void {
		$lexemeId = new LexemeId( 'L1234' );
		$redirectTarget = new LexemeId( 'L5678' );
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $lexemeId )
			->willReturn( LatestRevisionIdResult::redirect( 42, $redirectTarget ) );

		$metaDataRetriever = new EntityRevisionLookupLexemeRevisionMetadataRetriever( $entityRevisionLookup );
		$result = $metaDataRetriever->getLatestRevisionMetadata( $lexemeId );

		$this->assertTrue( $result->isRedirect() );
		$this->assertSame( $redirectTarget, $result->getRedirectTarget() );
	}

}
