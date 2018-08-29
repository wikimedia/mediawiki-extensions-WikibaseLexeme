<?php

namespace Wikibase\Lexeme\Tests\Merge;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Status;
use Title;
use User;
use WatchedItemStoreInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\Merge\LexemeMergeInteractor;
use Wikibase\Lexeme\Merge\LexemeMerger;
use Wikibase\Lexeme\Merge\LexemeRedirectCreationInteractor;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SummaryFormatter;

/**
 * @covers \Wikibase\Lexeme\Merge\LexemeMergeInteractor
 *
 * @license GPL-2.0-or-later
 */
class LexemeMergeInteractorTest extends TestCase {

	use PHPUnit4And6Compat;

	/**
	 * @var LexemeMerger
	 */
	private $lexemeMerger;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var EntityPermissionChecker
	 */
	private $permissionChecker;

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var LexemeRedirectCreationInteractor
	 */
	private $redirectInteractor;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var WatchedItemStoreInterface
	 */
	private $watchedItemStore;

	/**
	 * @var Lexeme
	 */
	private $sourceLexeme;

	/**
	 * @var Lexeme
	 */
	private $targetLexeme;

	public function setUp() {
		parent::setUp();

		$this->lexemeMerger = $this->newMockLexemeMerger();
		$this->entityRevisionLookup = $this->newMockEntityRevisionLookup();
		$this->entityStore = $this->newMockEntityStore();
		$this->permissionChecker = $this->newMockEntityPermissionChecker();
		$this->summaryFormatter = $this->newMockSummaryFormatter();
		$this->user = $this->newMockUser();
		$this->redirectInteractor = $this->newMockRedirectCreationInteractor();
		$this->entityTitleLookup = $this->newMockTitleLookup();
		$this->watchedItemStore = $this->newMockWatchedItemStore();
		$this->sourceLexeme = NewLexeme::havingId( 'L123' )->build();
		$this->targetLexeme = NewLexeme::havingId( 'L321' )->build();
	}

	public function testGivenMergeSucceeds_targetIsChangedCorrectly() {
		$this->sourceLexeme = NewLexeme::havingId( 'L234' )
			->withLanguage( 'Q1860' )
			->withLexicalCategory( 'Q1084' )
			->withLemma( 'en', 'sandbox' )
			->build();
		$this->targetLexeme = NewLexeme::havingId( 'L432' )
			->withLanguage( 'Q1860' )
			->withLexicalCategory( 'Q1084' )
			->withLemma( 'en-gb', 'sand box' )
			->build();

		$this->redirectInteractor = $this->newMockRedirectCreationInteractor();
		$this->redirectInteractor->expects( $this->once() )
			->method( 'createRedirect' )
			->with( $this->sourceLexeme->getId(), $this->targetLexeme->getId(), false );

		$this->lexemeMerger = new LexemeMerger(
			WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()
				->getMergeFactory()->getStatementsMerger()
		);

		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );

		$this->assertCount( 2, $this->targetLexeme->getLemmas() );
	}

	public function testGivenSuccessfulMerge_watchlistIsUpdated() {
		$this->watchedItemStore = $this->newMockWatchedItemStore();
		$this->watchedItemStore->expects( $this->once() )
			->method( 'duplicateAllAssociatedEntries' )
			->with(
				Title::newFromText( $this->sourceLexeme->getId()->getSerialization() ),
				Title::newFromText( $this->targetLexeme->getId()->getSerialization() )
			);

		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\ReferenceSameLexemeException
	 */
	public function testGivenIdenticalLexemeIds_throwsException() {
		$this->targetLexeme = $this->sourceLexeme->copy();

		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\MergingException
	 */
	public function testGivenLexemeMergerThrowsException_exceptionBubblesUp() {
		$this->lexemeMerger = $this->newMockLexemeMerger();
		$this->lexemeMerger->expects( $this->once() )
			->method( 'merge' )
			->willThrowException( $this->createMock( MergingException::class ) );

		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\PermissionDeniedException
	 */
	public function testGivenUserDoesNotHavePermission_throwsException() {
		$this->permissionChecker = $this->createMock( EntityPermissionChecker::class );
		$this->permissionChecker->method( 'getPermissionStatusForEntityId' )
			->willReturn( Status::newFatal( 'permission denied :(' ) );

		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\LexemeNotFoundException
	 */
	public function testGivenSourceNotFound_throwsException() {
		$this->entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$this->entityRevisionLookup->method( 'getEntityRevision' )
			->withConsecutive( $this->sourceLexeme->getId(), $this->targetLexeme->getId() )
			->willReturnOnConsecutiveCalls( null, new EntityRevision( $this->targetLexeme ) );

		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\LexemeNotFoundException
	 */
	public function testGivenTargetNotFound_throwsException() {
		$this->entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$this->entityRevisionLookup->method( 'getEntityRevision' )
			->withConsecutive( $this->sourceLexeme, $this->targetLexeme )
			->willReturnOnConsecutiveCalls( new EntityRevision( $this->sourceLexeme ), null );

		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	/**
	 * @dataProvider loadEntityExceptionProvider
	 *
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\LexemeLoadingException
	 */
	public function testGivenExceptionInLoadEntity_throwsAppropriateException( $exception ) {
		$this->entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$this->entityRevisionLookup->method( 'getEntityRevision' )
			->willThrowException( $exception );

		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\LexemeSaveFailedException
	 */
	public function testGivenEntitySaveFails_throwsException() {
		$this->entityStore = $this->newMockEntityStore();
		$this->entityStore->method( 'saveEntity' )
			->willThrowException( new StorageException() );

		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	private function newMergeInteractor() {
		$this->entityRevisionLookup->method( 'getEntityRevision' )
			->willReturnOnConsecutiveCalls(
				new EntityRevision( $this->sourceLexeme ),
				new EntityRevision( $this->targetLexeme )
			);

		return new LexemeMergeInteractor(
			$this->lexemeMerger,
			$this->entityRevisionLookup,
			$this->entityStore,
			$this->permissionChecker,
			$this->summaryFormatter,
			$this->user,
			$this->redirectInteractor,
			$this->entityTitleLookup,
			$this->watchedItemStore
		);
	}

	/**
	 * @return LexemeMerger|MockObject
	 */
	private function newMockLexemeMerger() {
		return $this->createMock( LexemeMerger::class );
	}

	/**
	 * @return EntityRevisionLookup|MockObject
	 */
	private function newMockEntityRevisionLookup() {
		return $this->createMock( EntityRevisionLookup::class );
	}

	/**
	 * @return EntityStore|MockObject
	 */
	private function newMockEntityStore() {
		$store = $this->createMock( EntityStore::class );
		$store->method( 'saveEntity' )
			->willReturn( $this->createMock( EntityRevision::class ) );

		return $store;
	}

	/**
	 * @return EntityPermissionChecker|MockObject
	 */
	private function newMockEntityPermissionChecker() {
		$permissionChecker = $this->createMock( EntityPermissionChecker::class );

		$permissionChecker->method( 'getPermissionStatusForEntityId' )
			->willReturn( new \Status() );

		return $permissionChecker;
	}

	/**
	 * @return SummaryFormatter|MockObject
	 */
	private function newMockSummaryFormatter() {
		return $this->createMock( SummaryFormatter::class );
	}

	/**
	 * @return User|MockObject
	 */
	private function newMockUser() {
		return $this->createMock( \User::class );
	}

	/**
	 * @return LexemeRedirectCreationInteractor|MockObject
	 */
	private function newMockRedirectCreationInteractor() {
		return $this->createMock( LexemeRedirectCreationInteractor::class );
	}

	/**
	 * @return EntityTitleStoreLookup|MockObject
	 */
	private function newMockTitleLookup() {
		$lookup = $this->createMock( EntityTitleStoreLookup::class );

		$lookup->method( 'getTitleForId' )
			->willReturnCallback( function ( EntityId $id ) {
				return Title::newFromText( $id->getSerialization() );
			} );

		return $lookup;
	}

	public function loadEntityExceptionProvider() {
		return [
			[ new StorageException() ],
			[ new RevisionedUnresolvedRedirectException(
				new LexemeId( 'L123' ),
				new LexemeId( 'L321' )
			) ]
		];
	}

	/**
	 * @return WatchedItemStoreInterface|MockObject
	 */
	private function newMockWatchedItemStore() {
		return $this->createMock( WatchedItemStoreInterface::class );
	}

}
