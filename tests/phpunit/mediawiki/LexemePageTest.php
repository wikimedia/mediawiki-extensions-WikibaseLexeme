<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Repo\WikibaseRepo;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class LexemePageTest extends WikibaseLexemeIntegrationTestCase {

	public function testGivenUserHasNoDeleteRights_lexemePageCannotBeDeleted() {
		$lexeme = $this->createTestLexeme( 'L123' );
		$pageIdentity = $this->lexemePageIdentity( $lexeme );
		$authority = $this->authorityWithGroups( [] );
		$deletePage = $this->getServiceContainer()
			->getDeletePageFactory()
			->newDeletePage( $pageIdentity, $authority );

		$status = $deletePage->deleteIfAllowed( '' );
		$this->assertFalse( $status->isOK() );
	}

	public function testGivenUserHasDeleteRights_lexemePageCanBeDeleted() {
		$lexeme = $this->createTestLexeme( 'L123' );
		$pageIdentity = $this->lexemePageIdentity( $lexeme );
		$authority = $this->authorityWithGroups( [ 'sysop' ] );
		$deletePage = $this->getServiceContainer()
			->getDeletePageFactory()
			->newDeletePage( $pageIdentity, $authority );

		$status = $deletePage->deleteIfAllowed( '' );
		$this->assertTrue( $status->isOK() );
	}

	private function createTestLexeme( $id ) {
		$lexeme = NewLexeme::havingId( $id )->build();
		$this->saveEntity( $lexeme );
		return $lexeme;
	}

	private function lexemePageIdentity( Lexeme $lexeme ): ProperPageIdentity {
		return WikibaseRepo::getEntityTitleStoreLookup( $this->getServiceContainer() )
			->getTitleForId( $lexeme->getId() )
			->toPageIdentity();
	}

	private function authorityWithGroups( array $groups ): Authority {
		return self::getTestUser( $groups )->getUser();
	}

}
