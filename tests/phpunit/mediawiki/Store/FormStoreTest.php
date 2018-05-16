<?php

namespace Wikibase\Lexeme\Tests\Store;

use PHPUnit4And6Compat;
use PHPUnit_Framework_MockObject_Matcher_InvokedCount;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit\Framework\TestCase;
use User;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Store\FormStore;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\Store\FormStore
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class FormStoreTest extends TestCase {

	use PHPUnit4And6Compat;

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	/**
	 * @var FormId
	 */
	private $formId;

	protected function setUp() {
		parent::setUp();

		$this->lexemeId = new LexemeId( 'L1' );
		$this->formId = new FormId( 'L1-F1' );
	}

	public function testAssignFreshId() {
		$instance = new FormStore(
			$this->newParentService( 'assignFreshId', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->setExpectedException( \DomainException::class );
		$instance->assignFreshId( $this->newLexeme() );
	}

	public function testGivenLexeme_saveEntityFails() {
		$instance = new FormStore(
			$this->newParentService( 'saveEntity', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->setExpectedException( ParameterTypeException::class );
		$instance->saveEntity( $this->newLexeme(), '', $this->newUser() );
	}

	public function testGivenFormId_saveEntityEditsFormOnLexeme() {
		$lexeme = $this->newLexeme();

		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->once() )
			->method( 'saveEntity' )
			->with( $lexeme )
			->willReturn( 'fromParentService' );

		$instance = new FormStore( $parentService, $this->newEntityRevisionLookup( $lexeme ) );

		$result = $instance->saveEntity( $this->newForm(), '', $this->newUser() );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenSaveFormWithFalseBaseRev_saveEntityEditsFormOnLexemeLoadedWithZeroRev() {
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

		$instance = new FormStore( $parentService, $lexemeLookup );

		$instance->saveEntity( $this->newForm(), '', $user, 0, false );
	}

	public function testGivenSaveFormWithNumberBaseRev_saveEntityEditsFormOnLexemeLoadedWithThatRev() {
		$user = $this->newUser();
		$lexeme = $this->newLexeme();

		$parentService = $this->getMock( EntityStore::class );
		$lexemeLookup = $this->getMock( EntityRevisionLookup::class );
		$lexemeLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $this->lexemeId, 47, 'master' )
			->willReturn( new EntityRevision( $lexeme ) );

		$instance = new FormStore( $parentService, $lexemeLookup );

		$instance->saveEntity( $this->newForm(), '', $user, 0, 47 );
	}

	public function testSaveRedirect() {
		$redirect = new EntityRedirect( $this->formId, $this->formId );
		$instance = new FormStore(
			$this->newParentService( 'saveRedirect', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->setExpectedException( \DomainException::class );
		$instance->saveRedirect( $redirect, '', $this->newUser() );
	}

	public function testGivenLexemeId_deleteEntityFails() {
		$instance = new FormStore(
			$this->newParentService( 'deleteEntity', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->setExpectedException( ParameterTypeException::class );
		$instance->deleteEntity( $this->lexemeId, '', $this->newUser() );
	}

	public function testGivenFormId_deleteEntityRemovesFormFromLexeme() {
		$lexeme = $this->newLexeme();
		$lexeme->expects( $this->once() )
			->method( 'removeForm' )
			->with( $this->formId );

		$parentService = $this->getMock( EntityStore::class );
		$parentService->expects( $this->never() )
			->method( 'deleteEntity' );
		$parentService->expects( $this->once() )
			->method( 'saveEntity' )
			->with( $lexeme );

		$instance = new FormStore( $parentService, $this->newEntityRevisionLookup( $lexeme ) );

		$instance->deleteEntity( $this->formId, '', $this->newUser() );
	}

	public function testGivenLexemeId_userWasLastToEditFails() {
		$instance = new FormStore(
			$this->newParentService( 'userWasLastToEdit', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->setExpectedException( ParameterTypeException::class );
		$instance->userWasLastToEdit( $this->newUser(), $this->lexemeId, 0 );
	}

	public function testGivenFormId_userWasLastToEditForwardsToParentService() {
		$instance = new FormStore(
			$this->newParentService( 'userWasLastToEdit', $this->once() ),
			$this->newEntityRevisionLookup()
		);

		$result = $instance->userWasLastToEdit( $this->newUser(), $this->formId, 0 );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenLexemeId_updateWatchlistFails() {
		$instance = new FormStore(
			$this->newParentService( 'updateWatchlist', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->setExpectedException( ParameterTypeException::class );
		$instance->updateWatchlist( $this->newUser(), $this->lexemeId, false );
	}

	public function testGivenFormId_updateWatchlistForwardsToParentService() {
		$instance = new FormStore(
			$this->newParentService( 'updateWatchlist', $this->once() ),
			$this->newEntityRevisionLookup()
		);

		$instance->updateWatchlist( $this->newUser(), $this->formId, false );
	}

	public function testGivenLexemeId_isWatchingFails() {
		$instance = new FormStore(
			$this->newParentService( 'isWatching', $this->never() ),
			$this->newEntityRevisionLookup()
		);

		$this->setExpectedException( ParameterTypeException::class );
		$instance->isWatching( $this->newUser(), $this->lexemeId );
	}

	public function testGivenFormId_isWatchingForwardsToParentService() {
		$instance = new FormStore(
			$this->newParentService( 'isWatching', $this->once() ),
			$this->newEntityRevisionLookup()
		);

		$result = $instance->isWatching( $this->newUser(), $this->formId );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testCanCreateWithCustomId() {
		$instance = new FormStore(
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
	 * @return Form
	 */
	private function newForm() {
		$mock = $this->getMockBuilder( Form::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->method( 'getId' )
			->willReturn( $this->formId );
		return $mock;
	}

}
