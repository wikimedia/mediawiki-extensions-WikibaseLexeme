<?php

namespace Wikibase\Lexeme\Tests\Merge;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Title;
use User;
use WatchedItemStoreInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\Domain\Authorization\LexemeAuthorizer;
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
use Wikibase\Lexeme\Tests\TestDoubles\FailingLexemeAuthorizer;
use Wikibase\Lexeme\Tests\TestDoubles\FakeLexemeRepository;
use Wikibase\Lexeme\Tests\TestDoubles\SucceedingLexemeAuthorizer;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor
 *
 * @license GPL-2.0-or-later
 */
class MergeLexemesInteractorTest extends TestCase {

	/**
	 * @var LexemeMerger|MockObject
	 */
	private $lexemeMerger;

	/**
	 * @var EntityStore|MockObject
	 */
	private $entityStore;

	/**
	 * @var LexemeAuthorizer
	 */
	private $authorizer;

	/**
	 * @var SummaryFormatter|MockObject
	 */
	private $summaryFormatter;

	/**
	 * @var User|MockObject
	 */
	private $user;

	/**
	 * @var LexemeRedirector|MockObject
	 */
	private $redirector;

	/**
	 * @var EntityTitleStoreLookup|MockObject
	 */
	private $entityTitleLookup;

	/**
	 * @var WatchedItemStoreInterface|MockObject
	 */
	private $watchedItemStore;

	/**
	 * @var FakeLexemeRepository
	 */
	private $lexemeRepository;

	/**
	 * @var Lexeme
	 */
	private $sourceLexeme;

	/**
	 * @var Lexeme
	 */
	private $targetLexeme;

	protected function setUp() : void {
		parent::setUp();

		$this->sourceLexeme = NewLexeme::havingId( 'L123' )->build();
		$this->targetLexeme = NewLexeme::havingId( 'L321' )->build();

		$this->lexemeRepository = new FakeLexemeRepository( $this->sourceLexeme, $this->targetLexeme );
		$this->lexemeMerger = $this->newMockLexemeMerger();
		$this->authorizer = new SucceedingLexemeAuthorizer();
		$this->summaryFormatter = $this->newMockSummaryFormatter();
		$this->redirector = $this->newMockRedirector();
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

		$this->lexemeRepository = new FakeLexemeRepository( $this->sourceLexeme, $this->targetLexeme );

		$this->redirector->expects( $this->once() )
			->method( 'redirect' )
			->with( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );

		$statementsMerger = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()
			->getMergeFactory()->getStatementsMerger();

		$crossRefValidator = $this->prophesize( NoCrossReferencingLexemeStatements::class );
		$crossRefValidator
			->validate( $this->sourceLexeme, $this->targetLexeme )
			->willReturn( true );
		$crossRefValidator = $crossRefValidator->reveal();
		/** @var \Wikibase\Lexeme\Domain\Merge\NoCrossReferencingLexemeStatements $crossRefValidator */

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
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );

		$this->assertCount(
			2,
			$this->lexemeRepository->getLexemeById( $this->targetLexeme->getId() )->getLemmas()
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
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	public function testGivenIdenticalLexemeIds_throwsException() {
		$this->targetLexeme = $this->sourceLexeme->copy();

		$this->expectException( ReferenceSameLexemeException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	public function testGivenLexemeMergerThrowsException_exceptionBubblesUp() {
		$this->lexemeMerger->expects( $this->once() )
			->method( 'merge' )
			->willThrowException( $this->getMockForAbstractClass( MergingException::class ) );

		$this->expectException( MergingException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	public function testGivenUserDoesNotHavePermission_throwsException() {
		$this->authorizer = new FailingLexemeAuthorizer();

		$this->expectException( PermissionDeniedException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	public function testGivenSourceNotFound_throwsException() {
		$this->lexemeRepository = new FakeLexemeRepository( $this->targetLexeme );

		$this->expectException( LexemeNotFoundException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	public function testGivenTargetNotFound_throwsException() {
		$this->lexemeRepository = new FakeLexemeRepository( $this->sourceLexeme );

		$this->expectException( LexemeNotFoundException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	public function testGivenExceptionInLoadEntity_throwsAppropriateException() {
		$this->lexemeRepository->throwOnRead();

		$this->expectException( LexemeLoadingException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	public function testGivenEntitySaveFails_throwsException() {
		$this->lexemeRepository->throwOnWrite();

		$this->expectException( LexemeSaveFailedException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId() );
	}

	private function newMergeInteractor() {
		return new \Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor(
			$this->lexemeMerger,
			$this->authorizer,
			$this->summaryFormatter,
			$this->redirector,
			$this->entityTitleLookup,
			$this->watchedItemStore,
			$this->lexemeRepository
		);
	}

	private function newMockLexemeMerger() {
		return $this->createMock( LexemeMerger::class );
	}

	private function newMockSummaryFormatter() {
		return $this->createMock( SummaryFormatter::class );
	}

	private function newMockRedirector() {
		return $this->createMock( LexemeRedirector::class );
	}

	private function newMockTitleLookup() {
		$lookup = $this->createMock( EntityTitleStoreLookup::class );

		$lookup->method( 'getTitleForId' )
			->willReturnCallback( function ( EntityId $id ) {
				return Title::newFromText( $id->getSerialization() );
			} );

		return $lookup;
	}

	private function newMockWatchedItemStore() {
		return $this->createMock( WatchedItemStoreInterface::class );
	}

}
