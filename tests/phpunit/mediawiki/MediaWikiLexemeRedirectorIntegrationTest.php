<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use PHPUnit\Framework\MockObject\MockObject;
use Status;
use Title;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRedirector;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\EditEntity\MediawikiEditFilterHookRunner;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRedirector
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiLexemeRedirectorIntegrationTest extends WikibaseLexemeIntegrationTestCase {

	/** @var EntityStore */
	private $entityStore;

	/** @var EntityLookup */
	private $entityLookup;

	/** @var WikibaseRepo */
	private $repo;

	protected function setUp() : void {
		parent::setUp();

		$this->tablesUsed[] = 'page';

		$this->repo = WikibaseRepo::getDefaultInstance();
		$this->entityStore = WikibaseRepo::getEntityStore();
		$this->entityLookup = $this->repo->getEntityLookup();
	}

	public function testCanCreateLexemeRedirect() {
		$source = NewLexeme::havingId( 'L123' )
			->build();
		$target = NewLexeme::havingId( 'L321' )
			->build();
		$this->saveEntity( $source );
		$this->saveEntity( $target );

		$interactor = $this->newRedirector();

		$interactor->redirect( $source->getId(), $target->getId() );

		$this->assertEquals(
			$target->getId(),
			WikibaseRepo::getStore()->getEntityRedirectLookup()
				->getRedirectForEntityId( $source->getId() )
		);
	}

	private function newRedirector() {
		return new MediaWikiLexemeRedirector(
			$this->repo->getEntityRevisionLookup(),
			WikibaseRepo::getEntityStore(),
			$this->getMockEntityPermissionChecker(),
			$this->getMockSummaryFormatter(),
			$this->getTestUser()->getUser(),
			$this->getMockEditFilterHookRunner(),
			WikibaseRepo::getStore()->getEntityRedirectLookup(),
			$this->getMockEntityTitleLookup(),
			false
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
	 * @return EditFilterHookRunner|MockObject
	 */
	private function getMockEditFilterHookRunner() {
		$hookRunner = $this->getMockBuilder( MediawikiEditFilterHookRunner::class )
			->disableOriginalConstructor()
			->getMock();
		$hookRunner->method( 'run' )
			->willReturn( Status::newGood() );

		return $hookRunner;
	}

}
