<?php

namespace Wikibase\Lexeme\Tests\Merge;

use IContextSource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Title;
use WatchedItemStoreInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRedirectorFactory;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRepositoryFactory;
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
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
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
	 * @var FakeLexemeRepository
	 */
	private $lexemeRepository;

	/** @var MediaWikiLexemeRepositoryFactory|MockObject */
	private $lexemeRepositoryFactory;

	/**
	 * @var Lexeme
	 */
	private $sourceLexeme;

	/**
	 * @var Lexeme
	 */
	private $targetLexeme;

	protected function setUp(): void {
		parent::setUp();

		$this->sourceLexeme = NewLexeme::havingId( 'L123' )->build();
		$this->targetLexeme = NewLexeme::havingId( 'L321' )->build();

		$this->lexemeRepository = new FakeLexemeRepository( $this->sourceLexeme, $this->targetLexeme );
		$this->lexemeRepositoryFactory = $this->createMock( MediaWikiLexemeRepositoryFactory::class );
		$this->lexemeRepositoryFactory->method( 'newFromContext' )
			->willReturnCallback( function () {
				return $this->lexemeRepository;
			} );
		$this->lexemeMerger = $this->createMock( LexemeMerger::class );
		$this->authorizer = new SucceedingLexemeAuthorizer();
		$this->summaryFormatter = $this->newMockSummaryFormatter();
		$this->context = $this->createMock( IContextSource::class );
		[ $this->redirector, $this->redirectorFactory ] = $this->newMockRedirectorAndFactory();
		$this->entityTitleLookup = $this->newMockTitleLookup();
		$this->watchedItemStore = $this->createMock( WatchedItemStoreInterface::class );
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
		$this->authorizer = new FailingLexemeAuthorizer();

		$this->expectException( PermissionDeniedException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId(), $this->context );
	}

	public function testGivenSourceNotFound_throwsException() {
		$this->lexemeRepository = new FakeLexemeRepository( $this->targetLexeme );

		$this->expectException( LexemeNotFoundException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId(), $this->context );
	}

	public function testGivenTargetNotFound_throwsException() {
		$this->lexemeRepository = new FakeLexemeRepository( $this->sourceLexeme );

		$this->expectException( LexemeNotFoundException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId(), $this->context );
	}

	public function testGivenExceptionInLoadEntity_throwsAppropriateException() {
		$this->lexemeRepository->throwOnRead();

		$this->expectException( LexemeLoadingException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId(), $this->context );
	}

	public function testGivenEntitySaveFails_throwsException() {
		$this->lexemeRepository->throwOnWrite();

		$this->expectException( LexemeSaveFailedException::class );
		$this->newMergeInteractor()
			->mergeLexemes( $this->sourceLexeme->getId(), $this->targetLexeme->getId(), $this->context );
	}

	private function newMergeInteractor() {
		return new MergeLexemesInteractor(
			$this->lexemeMerger,
			$this->authorizer,
			$this->summaryFormatter,
			$this->redirectorFactory,
			$this->entityTitleLookup,
			$this->watchedItemStore,
			$this->lexemeRepositoryFactory
		);
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

}
