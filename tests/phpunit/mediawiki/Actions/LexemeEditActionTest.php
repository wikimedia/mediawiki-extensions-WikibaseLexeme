<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\mediawiki;

use MediaWiki\Context\RequestContext;
use MediaWiki\Page\WikiPage;
use MediaWiki\Request\FauxRequest;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Rdbms\IDBAccessObject;

/**
 * @covers \Wikibase\Lexeme\Domain\Diff\LexemePatcher
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 */
class LexemeEditActionTest extends MediaWikiIntegrationTestCase {

	/** @see https://phabricator.wikimedia.org/T392372 */
	public function testUndoDeletionOfRestoredForm_doesNothing(): void {
		$services = $this->getServiceContainer();
		$entityStore = WikibaseRepo::getEntityStore( $services );
		$lexeme = NewLexeme::havingId( 'L1' )->withForm(
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'first form' )
		)->build();
		$initialRevision = $entityStore->saveEntity( $lexeme, 'set up', $this->newUser(), EDIT_NEW );
		$lexemeTitle = WikibaseRepo::getEntityTitleStoreLookup( $services )
			->getTitleForId( $lexeme->getId() );
		$lexeme = clone $lexeme;
		$lexeme->removeForm( new FormId( 'L1-F1' ) );
		$removedFormRevision = $entityStore->saveEntity( $lexeme, 'remove form', $this->newUser() );
		$lexeme = $initialRevision->getEntity();
		$restoredFormRevision = $entityStore->saveEntity( $lexeme, 'restore form', $this->newUser() );

		$this->submit( $services->getWikiPageFactory()->newFromTitle( $lexemeTitle ), [
			'undo' => $removedFormRevision->getRevisionId(),
		] );

		$expectedRevisionId = $restoredFormRevision->getRevisionId(); // no new edit since then
		$actualLatestRevisionId = $lexemeTitle->getLatestRevID( IDBAccessObject::READ_LATEST );
		$this->assertSame( $expectedRevisionId, $actualLatestRevisionId );
	}

	/** @see https://phabricator.wikimedia.org/T392372 */
	public function testUndoDeletionOfRestoredSense_doesNothing(): void {
		$services = $this->getServiceContainer();
		$entityStore = WikibaseRepo::getEntityStore( $services );
		$lexeme = NewLexeme::havingId( 'L1' )->withSense(
			NewSense::havingId( 'S1' )
				->withGloss( 'en', 'first sense' )
		)->build();
		$initialRevision = $entityStore->saveEntity( $lexeme, 'set up', $this->newUser(), EDIT_NEW );
		$lexemeTitle = WikibaseRepo::getEntityTitleStoreLookup( $services )
			->getTitleForId( $lexeme->getId() );
		$lexeme = clone $lexeme;
		$lexeme->removeSense( new SenseId( 'L1-S1' ) );
		$removedSenseRevision = $entityStore->saveEntity( $lexeme, 'remove sense', $this->newUser() );
		$lexeme = $initialRevision->getEntity();
		$restoredSenseRevision = $entityStore->saveEntity( $lexeme, 'restore sense', $this->newUser() );

		$this->submit( $services->getWikiPageFactory()->newFromTitle( $lexemeTitle ), [
			'undo' => $removedSenseRevision->getRevisionId(),
		] );

		$expectedRevisionId = $restoredSenseRevision->getRevisionId(); // no new edit since then
		$actualLatestRevisionId = $lexemeTitle->getLatestRevID( IDBAccessObject::READ_LATEST );
		$this->assertSame( $expectedRevisionId, $actualLatestRevisionId );
	}

	public function testRestoreFormsAndSensesAcrossSeveralEdits(): void {
		$services = $this->getServiceContainer();
		$entityStore = WikibaseRepo::getEntityStore( $services );
		$entityLookup = WikibaseRepo::getStore( $services )->getEntityLookup(
			Store::LOOKUP_CACHING_DISABLED,
			LookupConstants::LATEST_FROM_MASTER
		);
		$lexemeId = new LexemeId( 'L1' );
		$lexeme = NewLexeme::havingId( $lexemeId )->havingForm(
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'first form' )
		)->withForm(
			NewForm::havingId( 'F2' )
				->andRepresentation( 'en', 'second form' )
		)->withForm(
			NewForm::havingId( 'F3' )
				->andRepresentation( 'en', 'third form' )
		)->withSense(
			NewSense::havingId( 'S1' )
				->withGloss( 'en', 'first sense' )
		)->withSense(
			NewSense::havingId( 'S2' )
				->withGloss( 'en', 'second sense' )
		)->withSense(
			NewSense::havingId( 'S3' )
				->withGloss( 'en', 'third sense' )
		)->build();
		$initialRevision = $entityStore->saveEntity( $lexeme, 'set up', $this->newUser(), EDIT_NEW );
		$lexemeTitle = WikibaseRepo::getEntityTitleStoreLookup( $services )
			->getTitleForId( $lexemeId );
		$wikiPage = $services->getWikiPageFactory()->newFromTitle( $lexemeTitle );
		$lexeme->removeForm( new FormId( 'L1-F1' ) );
		$removeF1 = $entityStore->saveEntity( $lexeme, 'remove F1', $this->newUser() );
		$lexeme->removeSense( new SenseId( 'L1-S1' ) );
		$removeS1 = $entityStore->saveEntity( $lexeme, 'remove S1', $this->newUser() );
		$lexeme->removeForm( new FormId( 'L1-F2' ) );
		$removeF2 = $entityStore->saveEntity( $lexeme, 'remove F2', $this->newUser() );
		$lexeme->removeSense( new SenseId( 'L1-S2' ) );
		$removeS2 = $entityStore->saveEntity( $lexeme, 'remove S2', $this->newUser() );
		$lexeme->removeForm( new FormId( 'L1-F3' ) );
		$removeF3 = $entityStore->saveEntity( $lexeme, 'remove F3', $this->newUser() );
		$lexeme->removeSense( new SenseId( 'L1-S3' ) );
		$removeS3 = $entityStore->saveEntity( $lexeme, 'remove S3', $this->newUser() );

		$this->submit( $wikiPage, [
			'undo' => $removeS1->getRevisionId(),
		] );
		$this->assertSame(
			[ 'L1-S1' ],
			$this->getIds( $entityLookup->getEntity( $lexemeId ) )
		);

		$this->submit( $wikiPage, [
			'undoafter' => $removeS1->getRevisionId(),
			'undo' => $removeS2->getRevisionId(),
		] );
		$this->assertSame(
			[ 'L1-F2', 'L1-S1', 'L1-S2' ],
			$this->getIds( $entityLookup->getEntity( $lexemeId ) )
		);

		$this->submit( $wikiPage, [
			'undoafter' => $initialRevision->getRevisionId(),
			'undo' => $removeS2->getRevisionId(),
		] );
		$this->assertSame(
			[ 'L1-F1', 'L1-F2', 'L1-S1', 'L1-S2' ],
			$this->getIds( $entityLookup->getEntity( $lexemeId ) )
		);

		$this->submit( $wikiPage, [
			'undoafter' => $initialRevision->getRevisionId(),
			'undo' => $removeS3->getRevisionId(),
		] );
		$this->assertSame(
			[ 'L1-F1', 'L1-F2', 'L1-F3', 'L1-S1', 'L1-S2', 'L1-S3' ],
			$this->getIds( $entityLookup->getEntity( $lexemeId ) )
		);
	}

	private function newUser(): User {
		return $this->getTestUser()->getUser();
	}

	private function submit( WikiPage $wikiPage, array $args ): void {
		$user = $this->getTestUser()->getUser();
		$context = new RequestContext();
		$context->setUser( $user );
		$context->setRequest( new FauxRequest( $args + [
			'wpSave' => 1,
			'wpEditToken' => $user->getEditToken(),
		], true ) );
		$context->setLanguage( 'qqx' );
		$context->setWikiPage( $wikiPage );

		$action = $this->getServiceContainer()->getActionFactory()
			->getAction( 'submit', $wikiPage->getTitle(), $context );
		$action->show();
	}

	/** @return string[] */
	private function getIds( Lexeme $lexeme ): array {
		$formIds = array_map(
			static fn ( Form $form ) => $form->getId()->getSerialization(),
			$lexeme->getForms()->toArray()
		);
		$senseIds = array_map(
			static fn ( Sense $sense ) => $sense->getId()->getSerialization(),
			$lexeme->getSenses()->toArray()
		);
		return [ ...$formIds, ...$senseIds ];
	}

}
