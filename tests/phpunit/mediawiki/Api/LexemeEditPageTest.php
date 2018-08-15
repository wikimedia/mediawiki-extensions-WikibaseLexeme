<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Repo\Tests\NewItem;

/**
 * Tests undoing edits on a lexeme
 *
 * @coversNothing
 *
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class LexemeEditPageTest extends WikibaseLexemeApiTestCase {

	public function testGrammaticalFeatureUndo() {
		$gf1 = NewItem::withId( 'Q123' )->build();
		$gf2 = NewItem::withId( 'Q321' )->build();
		$lexeme = NewLexeme::havingId( 'L123' )
			->withForm( NewForm::havingId( 'F1' )
				->andGrammaticalFeature( $gf1->getId() ) )
			->build();

		$expected = $lexeme->copy();

		$this->saveEntity( $gf1 );
		$this->saveEntity( $gf2 );
		$this->saveEntity( $lexeme );

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'wbeditentity',
			'data' => json_encode( [
				'grammaticalFeatures' => [
					$gf1->getId()->getSerialization(),
					$gf2->getId()->getSerialization(),
				]
			] ),
			'id' => 'L123-F1',
		] );
		$this->assertEquals( 1, $result['success'] );

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'edit',
			'title' => 'Lexeme:' . $lexeme->getId(),
			'undo' => $result['entity']['lastrevid'],
		] );
		$this->assertEquals( 'Success', $result['edit']['result'] );

		$this->assertTrue(
			$expected->equals(
				$this->wikibaseRepo->getStore()
					->getEntityLookup( self::ENTITY_REVISION_LOOKUP_UNCACHED )
					->getEntity( $lexeme->getId() )
			)
		);
	}

	private function saveEntity( EntityDocument $entity ) {
		$this->entityStore->saveEntity(
			$entity,
			__CLASS__,
			$this->getTestUser()->getUser()
		);
	}

}
