<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use Article;
use User;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class LexemePageTest extends WikibaseLexemeIntegrationTestCase {

	public function testGivenUserHasNoDeleteRights_lexemePageCannotBeDeleted() {
		$lexeme = $this->createTestLexeme( 'L123' );
		$article = $this->newLexemePage( $lexeme );
		$article->setContext( $this->newContextWithUser(
			self::getTestUser( [] )->getUser() )
		);

		$this->setExpectedException( \PermissionsError::class );
		$article->delete();
	}

	public function testGivenUserHasDeleteRights_lexemePageCanBeDeleted() {
		$lexeme = $this->createTestLexeme( 'L123' );
		$article = $this->newLexemePage( $lexeme );
		$context = $this->newContextWithUser(
			self::getTestUser( [ 'sysop' ] )->getUser()
		);
		$context->setOutput( new \OutputPage( new \RequestContext() ) );
		$article->setContext( $context );

		$article->delete();

		$this->assertContains( 'Delete', $context->getOutput()->getPageTitle() );
	}

	private function createTestLexeme( $id ) {
		$lexeme = NewLexeme::havingId( $id )->build();
		WikibaseRepo::getDefaultInstance()
			->getEntityStore()
			->saveEntity( $lexeme, self::class, $this->getTestUser()->getUser() );
		return $lexeme;
	}

	private function newLexemePage( Lexeme $lexeme ) {
		return new Article(
			WikibaseRepo::getDefaultInstance()->getEntityTitleLookup()->getTitleForId( $lexeme->getId() )
		);
	}

	private function newContextWithUser( User $user ) {
		$context = new \RequestContext();
		$context->setUser( $user );

		return $context;
	}

}
