<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Interactors;

use PHPUnit\Framework\MockObject\MockObject;
use Status;
use Title;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Merge\LexemeRedirectCreationInteractor;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeIntegrationTestCase;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SummaryFormatter;

/**
 * @covers \Wikibase\Lexeme\Merge\LexemeRedirectCreationInteractor
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class LexemeRedirectCreationInteractorIntegrationTest extends WikibaseLexemeIntegrationTestCase {

	/** @var EntityStore */
	private $entityStore;

	/** @var EntityLookup */
	private $entityLookup;

	/** @var WikibaseRepo */
	private $repo;

	public function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'page';

		$this->repo = WikibaseRepo::getDefaultInstance();
		$this->entityStore = $this->repo->getEntityStore();
		$this->entityLookup = $this->repo->getEntityLookup();
	}

	public function testCanCreateLexemeRedirect() {
		$source = NewLexeme::havingId( 'L123' )
			->build();
		$target = NewLexeme::havingId( 'L321' )
			->build();
		$this->saveEntity( $source );
		$this->saveEntity( $target );

		$interactor = $this->newInteractor();

		$interactor->createRedirect( $source->getId(), $target->getId(), false );

		$this->assertEquals(
			$target->getId(),
			$this->repo->getStore()->getEntityRedirectLookup()
				->getRedirectForEntityId( $source->getId() )
		);
	}

	private function newInteractor() {
		return new LexemeRedirectCreationInteractor(
			$this->repo->getEntityRevisionLookup(),
			$this->repo->getEntityStore(),
			$this->getMockEntityPermissionChecker(),
			$this->getMockSummaryFormatter(),
			$this->getTestUser()->getUser(),
			$this->getMockEditFilterHookRunner(),
			$this->repo->getStore()->getEntityRedirectLookup(),
			$this->getMockEntityTitleLookup()
		);
	}

	/**
	 * @return SummaryFormatter|MockObject
	 */
	private function getMockSummaryFormatter() {
		$summaryFormatter = $this->getMockBuilder( SummaryFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$summaryFormatter->method( 'formatSummary' )
			->willReturnCallback( function ( FormatableSummary $summary ) {
				return 'MOCKFORMAT: ' .
					$summary->getMessageKey() .
					' ' .
					$summary->getUserSummary();
			} );
		return $summaryFormatter;
	}

	/**
	 * @return EntityPermissionChecker|MockObject
	 */
	private function getMockEntityPermissionChecker() {
		$permissionChecker = $this->getMockBuilder( EntityPermissionChecker::class )
			->getMock();
		$permissionChecker->method( 'getPermissionStatusForEntityId' )
			->willReturn( Status::newGood() );

		return $permissionChecker;
	}

	/**
	 * @return EntityTitleStoreLookup|MockObject
	 */
	private function getMockEntityTitleLookup() {
		$titleLookup = $this->getMockBuilder( EntityTitleStoreLookup::class )
			->getMock();

		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->willReturn( $this->createMock( Title::class ) );

		return $titleLookup;
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 * @throws \Exception
	 */
	private function getMockEditFilterHookRunner() {
		$hookRunner = $this->getMockBuilder( EditFilterHookRunner::class )
			->disableOriginalConstructor()
			->getMock();
		$hookRunner->method( 'run' )
			->willReturn( Status::newGood() );

		return $hookRunner;
	}

}
