<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataAccess\Store\SenseRevisionLookup;
use Wikibase\Lexeme\Domain\DummyObjects\NullSenseId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Lib\Store\LookupConstants;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\SenseRevisionLookup
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class SenseRevisionLookupTest extends TestCase {

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	/**
	 * @var SenseId
	 */
	private $senseId;

	protected function setUp(): void {
		parent::setUp();

		$this->lexemeId = new LexemeId( 'L1' );
		$this->senseId = new SenseId( 'L1-S1' );
	}

	public function testGivenLexemeId_getEntityRevisionFails() {
		$parentService = $this->createMock( EntityRevisionLookup::class );
		$instance = new SenseRevisionLookup( $parentService );

		$this->expectException( ParameterTypeException::class );
		$instance->getEntityRevision( $this->lexemeId );
	}

	public function testGivenSenseId_getEntityRevisionCallsParentServiceWithLexemeId() {
		$lexeme = $this->newLexeme();
		$revisionId = 23;

		$parentService = $this->createMock( EntityRevisionLookup::class );
		$parentService->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $this->lexemeId, $revisionId )
			->willReturn( new EntityRevision( $lexeme, $revisionId ) );
		$instance = new SenseRevisionLookup( $parentService );

		$result = $instance->getEntityRevision( $this->senseId, $revisionId );

		$expectedSense = $lexeme->getSenses()->toArray()[0];
		$this->assertEquals( new EntityRevision( $expectedSense, $revisionId ), $result );
	}

	public function testGivenLexemeId_getLatestRevisionIdFails() {
		$parentService = $this->createMock( EntityRevisionLookup::class );
		$instance = new SenseRevisionLookup( $parentService );

		$this->expectException( ParameterTypeException::class );
		$instance->getLatestRevisionId( $this->lexemeId );
	}

	public function testGivenNullSenseId_lookupIsNotPerformedAndNullReturned() {
		$parentService = $this->createMock( EntityRevisionLookup::class );
		$parentService
			->expects( $this->never() )
			->method( 'getEntityRevision' );

		$senseRevisionLookup = new SenseRevisionLookup( $parentService );

		$this->assertNull( $senseRevisionLookup->getEntityRevision( new NullSenseId() ) );
	}

	private function newLexeme() {
		return NewLexeme::havingId( $this->lexemeId )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'en', 'gloss' )
			)
			->build();
	}

	public function testGivenSenseId_getLatestRevisionIdCallsToParentServiceWithLexemeId() {
		$defaultMode = LookupConstants::LATEST_FROM_REPLICA;

		$parentService = $this->createMock( EntityRevisionLookup::class );
		$parentService->method( 'getLatestRevisionId' )
			->with( $this->lexemeId, $defaultMode )
			->willReturn( LatestRevisionIdResult::concreteRevision( 123, '20220101001122' ) );
		$parentService->method( 'getEntityRevision' )
			->with( $this->lexemeId, 123, $defaultMode )
			->willReturn( new EntityRevision( $this->newLexeme(), 123 ) );

		$instance = new SenseRevisionLookup( $parentService );

		$result = $this->extractConcreteRevision(
			$instance->getLatestRevisionId( $this->senseId )
		);
		$this->assertSame( 123, $result );
	}

	public function testLexemeDoesNotContainTheSense_getLatestRevisionIdReturnsNonexistentEntity() {
		$defaultMode = LookupConstants::LATEST_FROM_REPLICA;

		$parentService = $this->createMock( EntityRevisionLookup::class );
		$parentService->method( 'getLatestRevisionId' )
			->with( $this->lexemeId, $defaultMode )
			->willReturn( LatestRevisionIdResult::concreteRevision( 123, '20220101001122' ) );
		$parentService->method( 'getEntityRevision' )
			->with( $this->lexemeId, 123, $defaultMode )
			->willReturn( new EntityRevision( $this->newLexeme(), 123 ) );

		$instance = new SenseRevisionLookup( $parentService );

		$this->assertNonexistentRevision(
			$instance->getLatestRevisionId( new SenseId( 'L1-S200' ) )
		);
	}

	private function extractConcreteRevision( LatestRevisionIdResult $result ) {
		$shouldNotBeCalled = function () {
			$this->fail( 'Not a concrete revision given' );
		};

		return $result->onNonexistentEntity( $shouldNotBeCalled )
			->onRedirect( $shouldNotBeCalled )
			->onConcreteRevision( static function ( $revId ) {
				return $revId;
			} )
			->map();
	}

	private function assertNonexistentRevision( LatestRevisionIdResult $result ) {
		$shouldNotBeCalled = function () {
			$this->fail( 'Not a nonexistent revision given' );
		};

		return $result->onRedirect( $shouldNotBeCalled )
			->onConcreteRevision( $shouldNotBeCalled )
			->onNonexistentEntity(
				function () {
					$this->assertTrue( true );
				}
			)
			->map();
	}

}
