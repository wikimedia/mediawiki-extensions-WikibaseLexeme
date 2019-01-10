<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Store;

use BadMethodCallException;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiPageSubEntityMetaDataAccessor;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\LexemeSubEntityId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\MediaWikiPageSubEntityMetaDataAccessor
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiPageSubEntityMetaDataAccessorTest extends \PHPUnit_Framework_TestCase {

	use PHPUnit4And6Compat;

	public function testloadRevisionInformation_notImplemented() {
		$accessor = new MediaWikiPageSubEntityMetaDataAccessor(
			$this->createMock( WikiPageEntityMetaDataAccessor::class )
		);
		$this->setExpectedException( BadMethodCallException::class );
		$accessor->loadRevisionInformation( [], EntityRevisionLookup::LATEST_FROM_MASTER );
	}

	public function testloadRevisionInformationByRevisionId_notImplemented() {
		$accessor = new MediaWikiPageSubEntityMetaDataAccessor(
			$this->createMock( WikiPageEntityMetaDataAccessor::class )
		);
		$this->setExpectedException( BadMethodCallException::class );
		$accessor->loadRevisionInformationByRevisionId( $this->createMock( EntityId::class ), 1 );
	}

	public function testLoadLatestRevisionIds_returnsExpectedResponse() {
		$subIdString = 'L6392-F1';
		$lexemeIdString = 'L6392';
		$entityIds = [
			$this->getMockSubEntityId( $subIdString, $lexemeIdString )
		];
		$mockedRevIds = [ 1 ];
		$expectedEntityIds = array_map( function( $entityId ){ return $entityId->getLexemeId();
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
			->will( $this->returnValue( $lexemeIdString ) );
		$mockSubEntityId = $this->createMock( LexemeSubEntityId::class );
		$mockSubEntityId->method( 'getLexemeId' )
			->will( $this->returnValue( $mockLexemeId ) );
		$mockSubEntityId->method( 'getSerialization' )
			->will( $this->returnValue( $subIdString ) );
		return $mockSubEntityId;
	}

	private function getMediaWikiPageSubEntityMetaDataAccessor_mockAccessor(
		$expectedEntityIds = [],
		$mockedRevIds = []
	) {
		$latestRevisionIDs = [];
		foreach ( $expectedEntityIds as $key => $expectedEntityId ) {
			$latestRevisionIDs[ $expectedEntityId->getSerialization() ] = $mockedRevIds[ $key ];
		}
		$mockAccessorInternal = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$mockAccessorInternal->expects( $this->once() )
			->method( 'loadLatestRevisionIds' )
			->with( $expectedEntityIds )
			->willReturn( $latestRevisionIDs );
		return new MediaWikiPageSubEntityMetaDataAccessor(
			$mockAccessorInternal
		);
	}

}
