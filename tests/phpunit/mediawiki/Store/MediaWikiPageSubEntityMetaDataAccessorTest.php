<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Store;

use BadMethodCallException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiPageSubEntityMetaDataAccessor;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\LexemeSubEntityId;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\MediaWikiPageSubEntityMetaDataAccessor
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiPageSubEntityMetaDataAccessorTest extends TestCase {

	public function testloadRevisionInformationByRevisionId_notImplemented() {
		$accessor = new MediaWikiPageSubEntityMetaDataAccessor(
			$this->createMock( WikiPageEntityMetaDataAccessor::class )
		);
		$this->expectException( BadMethodCallException::class );
		$accessor->loadRevisionInformationByRevisionId( $this->createMock( EntityId::class ), 1 );
	}

	public function testLoadRevisionInformation() {
		$entityIds = [ new SenseId( 'L1-S1' ), new FormId( 'L2-F2' ) ];
		$mockedRevisionInformation = [ 'L1' => true, 'L2' => true ]; // using true as a dummy value
		$expectedReturn = [ 'L1-S1' => true, 'L2-F2' => true ]; // using true as a dummy value
		$entityDataAccessor = $this->getMediaWikiPageSubEntityMetaDataAccessor_mockAccessor(
			$entityIds,
			array_fill( 0, count( $entityIds ), '1' ), // dummy revision ids, irrelevant for this test
			$mockedRevisionInformation
		);

		$actualReturn = $entityDataAccessor->loadRevisionInformation(
			$entityIds, LookupConstants::LATEST_FROM_MASTER );

		$this->assertEquals( $expectedReturn, $actualReturn );
	}

	public function testLoadRevisionInformation_throwsWhenGivenInvalidIdType() {
		$senseId = new SenseId( 'L1-S1' );
		$lexemeId = new LexemeId( 'L1' );
		$entityIds = [
			$senseId,
			$lexemeId // invalid id type
		];

		$this->expectException( LogicException::class );

		$entityDataAccessor = $this->getMediaWikiPageSubEntityMetaDataAccessor_mockAccessor(
			[ $senseId, $lexemeId ],
			[ 1, 1 ],
			[ 1, 1 ]
		);

		$entityDataAccessor->loadRevisionInformation(
			$entityIds,
			LookupConstants::LATEST_FROM_MASTER
		);
	}

	public function testLoadLatestRevisionIds_returnsExpectedResponse() {
		$subIdString = 'L6392-F1';
		$lexemeIdString = 'L6392';
		$entityIds = [
			$this->getMockSubEntityId( $subIdString, $lexemeIdString )
		];
		$mockedRevIds = [ 1 ];
		$expectedEntityIds = array_map( static function ( $entityId ) {
			return $entityId->getLexemeId();
		}, $entityIds );
		$entityDataAccessor = $this->getMediaWikiPageSubEntityMetaDataAccessor_mockAccessor(
			$expectedEntityIds,
			$mockedRevIds
		);
		$revIds = $entityDataAccessor->loadLatestRevisionIds( $entityIds, '' );

		$this->assertEquals( [
			$subIdString => 1,
		], $revIds );
	}

	private function getMockSubEntityId( $subIdString, $lexemeIdString ) {
		$mockLexemeId = $this->createMock( LexemeId::class );
		$mockLexemeId->method( 'getSerialization' )
			->willReturn( $lexemeIdString );
		$mockSubEntityId = $this->createMock( LexemeSubEntityId::class );
		$mockSubEntityId->method( 'getLexemeId' )
			->willReturn( $mockLexemeId );
		$mockSubEntityId->method( 'getSerialization' )
			->willReturn( $subIdString );
		return $mockSubEntityId;
	}

	private function getMediaWikiPageSubEntityMetaDataAccessor_mockAccessor(
		$expectedEntityIds = [],
		$mockedRevIds = [],
		$mockedRevisions = []
	) {
		$latestRevisionIDs = [];
		foreach ( $expectedEntityIds as $key => $expectedEntityId ) {
			$latestRevisionIDs[ $expectedEntityId->getSerialization() ] = $mockedRevIds[ $key ];
		}
		$mockAccessorInternal = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$mockAccessorInternal->method( 'loadLatestRevisionIds' )
			->willReturn( $latestRevisionIDs );
		$mockAccessorInternal->method( 'loadRevisionInformation' )
			->willReturn( $mockedRevisions );
		return new MediaWikiPageSubEntityMetaDataAccessor(
			$mockAccessorInternal
		);
	}

}
