<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use FauxRequest;
use PHPUnit\Framework\MockObject\MockObject;
use RequestContext;
use Status;
use Title;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRedirector;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\FormatableSummary;
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
		$context = new RequestContext();
		$context->setRequest( new FauxRequest() );
		$context->setUser( $this->getTestUser()->getUser() );

		return new MediaWikiLexemeRedirector(
			WikibaseRepo::getEntityRevisionLookup(),
			$this->getEntityStore(),
			$this->getMockEntityPermissionChecker(),
			$this->getMockSummaryFormatter(),
			$context,
			$this->getMockEditFilterHookRunner(),
			WikibaseRepo::getStore()->getEntityRedirectLookup(),
			$this->getMockEntityTitleLookup(),
			false,
			[]
		);
	}

	/**
	 * @return SummaryFormatter|MockObject
	 */
	private function getMockSummaryFormatter() {
		$summaryFormatter = $this->createMock( SummaryFormatter::class );
		$summaryFormatter->method( 'formatSummary' )
			->willReturnCallback( static function ( FormatableSummary $summary ) {
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
		$permissionChecker = $this->createMock( EntityPermissionChecker::class );
		$permissionChecker->method( 'getPermissionStatusForEntityId' )
			->willReturn( Status::newGood() );

		return $permissionChecker;
	}

	/**
	 * @return EntityTitleStoreLookup|MockObject
	 */
	private function getMockEntityTitleLookup() {
		$titleLookup = $this->createMock( EntityTitleStoreLookup::class );
		$titleLookup->method( 'getTitleForId' )
			->willReturn( $this->createMock( Title::class ) );

		return $titleLookup;
	}

	/**
	 * @return EditFilterHookRunner|MockObject
	 */
	private function getMockEditFilterHookRunner() {
		$hookRunner = $this->createMock( MediawikiEditFilterHookRunner::class );
		$hookRunner->method( 'run' )
			->willReturn( Status::newGood() );

		return $hookRunner;
	}

}
