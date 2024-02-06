<?php

namespace Wikibase\Lexeme\Tests\Merge;

use FauxRequest;
use IContextSource;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Status;
use Title;
use WatchedItemStoreInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRedirectorFactory;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRepositoryFactory;
use Wikibase\Lexeme\Domain\LexemeRedirector;
use Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeLoadingException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeNotFoundException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeSaveFailedException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\PermissionDeniedException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\ReferenceSameLexemeException;
use Wikibase\Lexeme\Domain\Merge\LexemeFormsMerger;
use Wikibase\Lexeme\Domain\Merge\LexemeMerger;
use Wikibase\Lexeme\Domain\Merge\LexemeSensesMerger;
use Wikibase\Lexeme\Domain\Merge\NoCrossReferencingLexemeStatements;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\EditEntity\EditEntity;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor
 *
 * @group Database
 * @license GPL-2.0-or-later
 */
class MergeLexemesInteractorTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var LexemeMerger|MockObject
	 */
	private $lexemeMerger;

	/**
	 * @var EntityPermissionChecker
	 */
	private $permissionChecker;

	/**
	 * @var SummaryFormatter|MockObject
	 */
	private $summaryFormatter;

	/**
	 * @var IContextSource|MockObject
	 */
	private $context;

	/**
	 * @var LexemeRedirector|MockObject
	 */
	private $redirector;

	/** @var MediaWikiLexemeRedirectorFactory|MockObject */
	private $redirectorFactory;

	/**
	 * @var EntityTitleStoreLookup|MockObject
	 */
	private $entityTitleLookup;

	/**
	 * @var WatchedItemStoreInterface|MockObject
	 */
	private $watchedItemStore;

	/**
	 * @var ?Lexeme
	 */
	private $sourceLexeme;

	/**
	 * @var ?Lexeme
	 */
	private $targetLexeme;

	protected function setUp(): void {
		parent::setUp();
		$this->tablesUsed[] = 'page';

		$this->sourceLexeme = NewLexeme::havingId( 'L123' )->build();
		$this->targetLexeme = NewLexeme::havingId( 'L321' )->build();

		$this->lexemeMerger = $this->newMockLexemeMerger();
		$this->permissionChecker = $this->createConfiguredMock( EntityPermissionChecker::class, [
			'getPermissionStatusForEntityId' => Status::newGood(),
		] );
		$this->summaryFormatter = $this->newMockSummaryFormatter();
		$this->context = $this->createMock( IContextSource::class );
		$this->context->method( 'getUser' )
			->willReturnCallback( fn () => $this->getTestUser()->getUser() );
		$this->context->method( 'getRequest' )
			->willReturn( new FauxRequest() );
		$this->context->method( 'getConfig' )
			->willReturn( $this->getServiceContainer()->getMainConfig() );
		[ $this->redirector, $this->redirectorFactory ] = $this->newMockRedirectorAndFactory();
		$this->entityTitleLookup = $this->newMockTitleLookup();
		$this->watchedItemStore = $this->newMockWatchedItemStore();
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

		$this->redirector->expects( $this->once() )
			->method( 'redirect' )
			->with( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );

		$statementsMerger = WikibaseRepo::getChangeOpFactoryProvider()
			->getMergeFactory()->getStatementsMerger();

		$crossRefValidator = $this->createMock( NoCrossReferencingLexemeStatements::class );
		$crossRefValidator->method( 'validate' )
			->with( $this->sourceLexeme, $this->targetLexeme )
			->willReturn( true );

		$this->lexemeMerger = new LexemeMerger(
			$statementsMerger,
			new LexemeFormsMerger(
				$statementsMerger,
				new GuidGenerator()
			),
			new LexemeSensesMerger(
				new GuidGenerator()
			),
			$crossRefValidator
		);

		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId(), $this->context );

		$this->assertCount(
			2,
			WikibaseRepo::getEntityLookup( $this->getServiceContainer() )
				->getEntity( $this->targetLexeme->getId() )
				->getLemmas()
		);
	}

	public function testGivenSuccessfulMerge_watchlistIsUpdated() {
		$this->watchedItemStore->expects( $this->once() )
			->method( 'duplicateAllAssociatedEntries' )
			->with(
				Title::newFromText( $this->sourceLexeme->getId()->getSerialization() ),
				Title::newFromText( $this->targetLexeme->getId()->getSerialization() )
			);

		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId(), $this->context );
	}

	public function testGivenIdenticalLexemeIds_throwsException() {
		$this->targetLexeme = $this->sourceLexeme->copy();

		$this->expectException( ReferenceSameLexemeException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId(), $this->context );
	}

	public function testGivenLexemeMergerThrowsException_exceptionBubblesUp() {
		$this->lexemeMerger->expects( $this->once() )
			->method( 'merge' )
			->willThrowException( $this->getMockForAbstractClass( MergingException::class ) );

		$this->expectException( MergingException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId(), $this->context );
	}

	public function testGivenUserDoesNotHavePermission_throwsException() {
		$this->permissionChecker = $this->createConfiguredMock( EntityPermissionChecker::class, [
			'getPermissionStatusForEntityId' => Status::newFatal( 'message' ),
		] );

		$this->expectException( PermissionDeniedException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId(), $this->context );
	}

	public function testGivenSourceNotFound_throwsException() {
		$sourceLexemeId = $this->sourceLexeme->getId();
		$this->sourceLexeme = null;

		$this->expectException( LexemeNotFoundException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $sourceLexemeId, $this->targetLexeme->getId(), $this->context );
	}

	public function testGivenTargetNotFound_throwsException() {
		$targetLexemeId = $this->targetLexeme->getId();
		$this->targetLexeme = null;

		$this->expectException( LexemeNotFoundException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $targetLexemeId, $this->context );
	}

	public function testGivenExceptionInLoadEntity_throwsAppropriateException() {
		$throwingEntityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$throwingEntityRevisionLookup->method( 'getLatestRevisionId' )
			->willReturn( LatestRevisionIdResult::concreteRevision( 123, '123' ) );
		$throwingEntityRevisionLookup->method( 'getEntityRevision' )
			->willThrowException( new StorageException() );
		$this->setService( 'WikibaseRepo.EntityRevisionLookup', $throwingEntityRevisionLookup );

		$this->expectException( LexemeLoadingException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId(), $this->context );
	}

	public function testGivenEntitySaveFails_throwsException() {
		$failingEditEntity = $this->createMock( EditEntity::class );
		$failingEditEntity->method( 'attemptSave' )
			->willReturn( Status::newFatal( 'failed-save' ) );
		$this->setService( 'WikibaseRepo.EditEntityFactory',
			$this->createConfiguredMock( MediaWikiEditEntityFactory::class, [
				'newEditEntity' => $failingEditEntity,
			] ) );

		$this->expectException( LexemeSaveFailedException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId(), $this->context );
	}

	private function newMergeInteractor() {
		$services = $this->getServiceContainer();
		$entityStore = WikibaseRepo::getEntityStore( $services );
		$user = $this->getTestUser()->getUser();
		if ( $this->sourceLexeme !== null ) {
			$entityStore->saveEntity( $this->sourceLexeme, 'test setup', $user );
		}
		if ( $this->targetLexeme !== null ) {
			$entityStore->saveEntity( $this->targetLexeme, 'test setup', $user );
		}
		$permissionManager = $services->getPermissionManager();
		$lexemeRepositoryFactory = new MediaWikiLexemeRepositoryFactory(
			$entityStore,
			WikibaseRepo::getEntityRevisionLookup( $services ), $permissionManager
		);

		return new MergeLexemesInteractor(
			$this->lexemeMerger,
			$this->summaryFormatter,
			$this->redirectorFactory,
			$this->permissionChecker,
			$permissionManager,
			$this->entityTitleLookup,
			$this->watchedItemStore,
			$lexemeRepositoryFactory,
			WikibaseRepo::getEditEntityFactory( $services )
		);
	}

	private function newMockLexemeMerger() {
		return $this->createMock( LexemeMerger::class );
	}

	private function newMockSummaryFormatter() {
		$summaryFormatter = $this->createMock( SummaryFormatter::class );
		$summaryFormatter->method( 'formatSummary' )
			->willReturn( '' );
		return $summaryFormatter;
	}

	private function newMockRedirectorAndFactory() {
		$redirector = $this->createMock( LexemeRedirector::class );
		$factory = $this->createMock( MediaWikiLexemeRedirectorFactory::class );
		$factory->method( 'newFromContext' )
			->with( $this->context, $this->anything(), $this->anything() )
			->willReturn( $redirector );
		return [ $redirector, $factory ];
	}

	private function newMockTitleLookup() {
		$lookup = $this->createMock( EntityTitleStoreLookup::class );

		$lookup->method( 'getTitleForId' )
			->willReturnCallback( static function ( EntityId $id ) {
				return Title::newFromText( $id->getSerialization() );
			} );

		return $lookup;
	}

	private function newMockWatchedItemStore() {
		return $this->createMock( WatchedItemStoreInterface::class );
	}

}
