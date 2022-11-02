<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Store;

use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use User;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lexeme\DataAccess\Store\SenseStore;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\SenseStore
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class SenseStoreTest extends TestCase {

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	/**
	 * @var SenseId
	 */
	private $senseIdOne;

	/**
	 * @var SenseId
	 */
	private $senseIdTwo;

	protected function setUp(): void {
		parent::setUp();

		$this->lexemeId = new LexemeId( 'L1' );
		$this->senseIdOne = new SenseId( 'L1-S1' );
		$this->senseIdTwo = new SenseId( 'L2-S1' );
	}

	public function testAssignFreshId() {
		$instance = new SenseStore(
			$this->newParentService( 'assignFreshId', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->expectException( \DomainException::class );
		$instance->assignFreshId( $this->newLexeme() );
	}

	public function testAssignFreshIdOnBlankSense_causesNoException() {
		$instance = new SenseStore(
			$this->newParentService( 'assignFreshId', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$blankSense = new BlankSense();

		$this->assertNull( $instance->assignFreshId( $blankSense ) );
	}

	public function testGivenLexeme_saveEntityFails() {
		$instance = new SenseStore(
			$this->newParentService( 'saveEntity', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->expectException( ParameterTypeException::class );
		$instance->saveEntity( $this->newLexeme(), '', $this->createMock( User::class ) );
	}

	public function testGivenSenseId_saveEntityEditsSenseOnLexeme() {
		$lexeme = $this->newLexeme();
		$user = $this->createMock( User::class );

		$parentService = $this->createMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'saveEntity' )
			->with( $lexeme, '', $user )
			->willReturn( 'fromParentService' );

		$instance = new SenseStore( $parentService, $this->newEntityRevisionLookup( $lexeme ) );

		$result = $instance->saveEntity( $this->newSense(), '', $user );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenSaveEntityWithDefaultFlag_editNewFlagNotPassedToParentService() {
		$lexeme = $this->newLexeme();
		$user = $this->createMock( User::class );

		$parentService = $this->createMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'saveEntity' )
			->will( $this->returnCallback(
				function ( Lexeme $lexeme, $summary, $user, $flags, $baseRevId, $tags ) {
					$this->assertSame( 0, $flags );
					return 'fromParentService';
				}
			) );

		$instance = new SenseStore( $parentService, $this->newEntityRevisionLookup( $lexeme ) );

		$result = $instance->saveEntity( $this->newSense(), '', $user );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenSaveEntityWithEditNewFlag_editNewFlagNotPassedToParentService() {
		$lexeme = $this->newLexeme();
		$user = $this->createMock( User::class );

		$parentService = $this->createMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'saveEntity' )
			->will( $this->returnCallback(
				function ( Lexeme $lexeme, $summary, $user, $flags, $baseRevId, $tags ) {
					$this->assertSame( 0, $flags );
					return 'fromParentService';
				}
			) );

		$instance = new SenseStore( $parentService, $this->newEntityRevisionLookup( $lexeme ) );

		$result = $instance->saveEntity( $this->newSense(), '', $user, EDIT_NEW );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenSaveSenseWithFalseBaseRev_saveEntityEditsSenseOnLexemeLoadedWith0Rev() {
		$user = $this->createMock( User::class );
		$lexeme = $this->newLexeme();

		$parentService = $this->createMock( EntityStore::class );
		$lexemeLookup = $this->createMock( EntityRevisionLookup::class );
		$lexemeLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->will( $this->returnCallback( function ( LexemeId $lexemeId, $revId, $mode ) use ( $lexeme ) {
				$this->assertSame( 0, $revId, 'strict assertion - 0 !== false' );
				return new EntityRevision( $lexeme );
			} ) );

		$instance = new SenseStore( $parentService, $lexemeLookup );

		$instance->saveEntity( $this->newSense(), '', $user, 0, false );
	}

	public function testGivenSaveSenseWithXBaseRev_saveEntityEditsSenseOnLexemeLoadedWithXRev() {
		$user = $this->createMock( User::class );
		$lexeme = $this->newLexeme();

		$parentService = $this->createMock( EntityStore::class );
		$lexemeLookup = $this->createMock( EntityRevisionLookup::class );
		$lexemeLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $this->lexemeId, 47, 'master' )
			->willReturn( new EntityRevision( $lexeme ) );

		$instance = new SenseStore( $parentService, $lexemeLookup );

		$instance->saveEntity( $this->newSense(), '', $user, 0, 47 );
	}

	public function testGivenSaveEntityWithTags_tagsPassedToParentService() {
		$lexeme = $this->newLexeme();
		$user = $this->createMock( User::class );

		$parentService = $this->createMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'saveEntity' )
			->will( $this->returnCallback(
				function ( Lexeme $lexeme, $summary, $user, $flags, $baseRevId, $tags ) {
					$this->assertSame( [ 'test', 'tag' ], $tags );
					return 'fromParentService';
				}
			) );

		$instance = new SenseStore( $parentService, $this->newEntityRevisionLookup( $lexeme ) );

		$result = $instance->saveEntity( $this->newSense(), '', $user, 0, false, [ 'test', 'tag' ] );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testSaveRedirect() {
		$redirect = new EntityRedirect( $this->senseIdOne, $this->senseIdTwo );
		$instance = new SenseStore(
			$this->newParentService( 'saveRedirect', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->expectException( \DomainException::class );
		$instance->saveRedirect( $redirect, '', $this->createMock( User::class ) );
	}

	public function testGivenLexemeId_deleteEntityFails() {
		$instance = new SenseStore(
			$this->newParentService( 'deleteEntity', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->expectException( ParameterTypeException::class );
		$instance->deleteEntity( $this->lexemeId, '', $this->createMock( User::class ) );
	}

	public function testGivenSenseId_deleteEntityRemovesSenseFromLexeme() {
		$lexeme = $this->newLexeme();
		$lexeme->expects( $this->once() )
			->method( 'removeSense' )
			->with( $this->senseIdOne );

		$parentService = $this->createMock( EntityStore::class );
		$parentService->expects( $this->never() )
			->method( 'deleteEntity' );
		$parentService->expects( $this->once() )
			->method( 'saveEntity' )
			->with( $lexeme );

		$instance = new SenseStore( $parentService, $this->newEntityRevisionLookup( $lexeme ) );

		$instance->deleteEntity( $this->senseIdOne, '', $this->createMock( User::class ) );
	}

	public function testGivenLexemeId_userWasLastToEditFails() {
		$instance = new SenseStore(
			$this->newParentService( 'userWasLastToEdit', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->expectException( ParameterTypeException::class );
		$instance->userWasLastToEdit( $this->createMock( User::class ), $this->lexemeId, 0 );
	}

	public function testGivenSenseId_userWasLastToEditForwardsToParentService() {
		$instance = new SenseStore(
			$this->newParentService( 'userWasLastToEdit', $this->once() ),
			$this->newEntityRevisionLookup()
		);

		$result = $instance->userWasLastToEdit( $this->createMock( User::class ), $this->senseIdOne, 0 );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenLexemeId_updateWatchlistFails() {
		$instance = new SenseStore(
			$this->newParentService( 'updateWatchlist', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->expectException( ParameterTypeException::class );
		$instance->updateWatchlist( $this->createMock( User::class ), $this->lexemeId, false );
	}

	public function testGivenSenseId_updateWatchlistForwardsToParentService() {
		$instance = new SenseStore(
			$this->newParentService( 'updateWatchlist', $this->once() ),
			$this->newEntityRevisionLookup()
		);

		$instance->updateWatchlist( $this->createMock( User::class ), $this->senseIdOne, false );
	}

	public function testGivenLexemeId_isWatchingFails() {
		$instance = new SenseStore(
			$this->newParentService( 'isWatching', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->expectException( ParameterTypeException::class );
		$instance->isWatching( $this->createMock( User::class ), $this->lexemeId );
	}

	public function testGivenSenseId_isWatchingForwardsToParentService() {
		$instance = new SenseStore(
			$this->newParentService( 'isWatching', $this->once() ),
			$this->newEntityRevisionLookup()
		);

		$result = $instance->isWatching( $this->createMock( User::class ), $this->senseIdOne );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testCanCreateWithCustomId() {
		$instance = new SenseStore(
			$this->newParentService( 'canCreateWithCustomId', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$result = $instance->canCreateWithCustomId( $this->lexemeId );
		$this->assertFalse( $result );
	}

	/**
	 * @param string $parentMethod
	 * @param InvokedCount $expectedCalls
	 *
	 * @return EntityStore
	 */
	private function newParentService(
		$parentMethod,
		$expectedCalls
	) {
		$parentService = $this->createMock( EntityStore::class );
		$parentService->expects( $expectedCalls )
			->method( $parentMethod )
			->willReturn( 'fromParentService' );
		return $parentService;
	}

	/**
	 * @param Lexeme|null $expectedLexeme
	 *
	 * @return EntityRevisionLookup
	 */
	private function newEntityRevisionLookup( Lexeme $expectedLexeme = null ) {
		$lookup = $this->createMock( EntityRevisionLookup::class );

		if ( $expectedLexeme ) {
			$lookup->expects( $this->once() )
				->method( 'getEntityRevision' )
				->with( $expectedLexeme->getId() )
				->willReturn( new EntityRevision( $expectedLexeme ) );
		} else {
			$lookup->expects( $this->never() )
				->method( 'getEntityRevision' );
		}

		return $lookup;
	}

	/**
	 * @return Lexeme|MockObject
	 */
	private function newLexeme() {
		$mock = $this->createMock( Lexeme::class );
		$mock->method( 'getId' )
			->willReturn( $this->lexemeId );
		return $mock;
	}

	/**
	 * @return Sense
	 */
	private function newSense() {
		$mock = $this->createMock( Sense::class );
		$mock->method( 'getId' )
			->willReturn( $this->senseIdOne );
		return $mock;
	}

}
