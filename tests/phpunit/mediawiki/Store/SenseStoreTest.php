<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Store;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use PHPUnit_Framework_MockObject_Matcher_InvokedCount;
use PHPUnit_Framework_MockObject_MockObject;
use User;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\DataAccess\Store\SenseStore;
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

	use PHPUnit4And6Compat;

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	/**
	 * @var SenseId
	 */
	private $senseId;

	protected function setUp() {
		parent::setUp();

		$this->lexemeId = new LexemeId( 'L1' );
		$this->senseId = new SenseId( 'L1-S1' );
	}

	public function testAssignFreshId() {
		$instance = new SenseStore(
			$this->newParentService( 'assignFreshId', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->setExpectedException( \DomainException::class );
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

		$this->setExpectedException( ParameterTypeException::class );
		$instance->saveEntity( $this->newLexeme(), '', $this->newUser() );
	}

	public function testGivenSenseId_saveEntityEditsSenseOnLexeme() {
		$lexeme = $this->newLexeme();
		$user = $this->newUser();

		$parentService = $this->getMock( EntityStore::class );
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
		$user = $this->newUser();

		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'saveEntity' )
			->will( $this->returnCallback( function ( Lexeme $lexeme, $summary, $user, $flags, $baseRevId ) {
				$this->assertSame( 0, $flags );
				return 'fromParentService';
			} ) );

		$instance = new SenseStore( $parentService, $this->newEntityRevisionLookup( $lexeme ) );

		$result = $instance->saveEntity( $this->newSense(), '', $user );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenSaveEntityWithEditNewFlag_editNewFlagNotPassedToParentService() {
		$lexeme = $this->newLexeme();
		$user = $this->newUser();

		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'saveEntity' )
			->will( $this->returnCallback( function ( Lexeme $lexeme, $summary, $user, $flags, $baseRevId ) {
				$this->assertSame( 0, $flags );
				return 'fromParentService';
			} ) );

		$instance = new SenseStore( $parentService, $this->newEntityRevisionLookup( $lexeme ) );

		$result = $instance->saveEntity( $this->newSense(), '', $user, EDIT_NEW );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenSaveSenseWithFalseBaseRev_saveEntityEditsSenseOnLexemeLoadedWith0Rev() {
		$user = $this->newUser();
		$lexeme = $this->newLexeme();

		$parentService = $this->getMock( EntityStore::class );
		$lexemeLookup = $this->getMock( EntityRevisionLookup::class );
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
		$user = $this->newUser();
		$lexeme = $this->newLexeme();

		$parentService = $this->getMock( EntityStore::class );
		$lexemeLookup = $this->getMock( EntityRevisionLookup::class );
		$lexemeLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $this->lexemeId, 47, 'master' )
			->willReturn( new EntityRevision( $lexeme ) );

		$instance = new SenseStore( $parentService, $lexemeLookup );

		$instance->saveEntity( $this->newSense(), '', $user, 0, 47 );
	}

	public function testSaveRedirect() {
		$redirect = new EntityRedirect( $this->senseId, $this->senseId );
		$instance = new SenseStore(
			$this->newParentService( 'saveRedirect', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->setExpectedException( \DomainException::class );
		$instance->saveRedirect( $redirect, '', $this->newUser() );
	}

	public function testGivenLexemeId_deleteEntityFails() {
		$instance = new SenseStore(
			$this->newParentService( 'deleteEntity', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->setExpectedException( ParameterTypeException::class );
		$instance->deleteEntity( $this->lexemeId, '', $this->newUser() );
	}

	public function testGivenSenseId_deleteEntityRemovesSenseFromLexeme() {
		$lexeme = $this->newLexeme();
		$lexeme->expects( $this->once() )
			->method( 'removeSense' )
			->with( $this->senseId );

		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->never() )
			->method( 'deleteEntity' );
		$parentService->expects( $this->once() )
			->method( 'saveEntity' )
			->with( $lexeme );

		$instance = new SenseStore( $parentService, $this->newEntityRevisionLookup( $lexeme ) );

		$instance->deleteEntity( $this->senseId, '', $this->newUser() );
	}

	public function testGivenLexemeId_userWasLastToEditFails() {
		$instance = new SenseStore(
			$this->newParentService( 'userWasLastToEdit', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->setExpectedException( ParameterTypeException::class );
		$instance->userWasLastToEdit( $this->newUser(), $this->lexemeId, 0 );
	}

	public function testGivenSenseId_userWasLastToEditForwardsToParentService() {
		$instance = new SenseStore(
			$this->newParentService( 'userWasLastToEdit', $this->once() ),
			$this->newEntityRevisionLookup()
		);

		$result = $instance->userWasLastToEdit( $this->newUser(), $this->senseId, 0 );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenLexemeId_updateWatchlistFails() {
		$instance = new SenseStore(
			$this->newParentService( 'updateWatchlist', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->setExpectedException( ParameterTypeException::class );
		$instance->updateWatchlist( $this->newUser(), $this->lexemeId, false );
	}

	public function testGivenSenseId_updateWatchlistForwardsToParentService() {
		$instance = new SenseStore(
			$this->newParentService( 'updateWatchlist', $this->once() ),
			$this->newEntityRevisionLookup()
		);

		$instance->updateWatchlist( $this->newUser(), $this->senseId, false );
	}

	public function testGivenLexemeId_isWatchingFails() {
		$instance = new SenseStore(
			$this->newParentService( 'isWatching', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->setExpectedException( ParameterTypeException::class );
		$instance->isWatching( $this->newUser(), $this->lexemeId );
	}

	public function testGivenSenseId_isWatchingForwardsToParentService() {
		$instance = new SenseStore(
			$this->newParentService( 'isWatching', $this->once() ),
			$this->newEntityRevisionLookup()
		);

		$result = $instance->isWatching( $this->newUser(), $this->senseId );
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
	 * @param PHPUnit_Framework_MockObject_Matcher_InvokedCount $expectedCalls
	 *
	 * @return EntityStore
	 */
	private function newParentService(
		$parentMethod,
		$expectedCalls
	) {
		$parentService = $this->getMock( EntityStore::class );
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
		$lookup = $this->getMock( EntityRevisionLookup::class );

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
	 * @return User
	 */
	private function newUser() {
		$mock = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();
		return $mock;
	}

	/**
	 * @return Lexeme|PHPUnit_Framework_MockObject_MockObject
	 */
	private function newLexeme() {
		$mock = $this->getMockBuilder( Lexeme::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->method( 'getId' )
			->willReturn( $this->lexemeId );
		return $mock;
	}

	/**
	 * @return Sense
	 */
	private function newSense() {
		$mock = $this->getMockBuilder( Sense::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->method( 'getId' )
			->willReturn( $this->senseId );
		return $mock;
	}

}
