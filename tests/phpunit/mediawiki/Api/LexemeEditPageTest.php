<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;

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
		$this->assertSame( 1, $result['success'] );

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'edit',
			'title' => 'Lexeme:' . $lexeme->getId(),
			'undo' => $result['entity']['lastrevid'],
		] );
		$this->assertEquals( 'Success', $result['edit']['result'] );

		$this->assertTrue(
			$expected->equals(
				WikibaseRepo::getStore()
					->getEntityLookup( Store::LOOKUP_CACHING_DISABLED )
					->getEntity( $lexeme->getId() )
			)
		);
	}

	public function testSenseAdditionUndo() {
		$emptyLexeme = NewLexeme::havingId( 'L123' )->build();
		$expected = $emptyLexeme->copy();

		$this->saveEntity( $emptyLexeme );

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'wbladdsense',
			'lexemeId' => 'L123',
			'data' => json_encode( [
				'glosses' => [
					'en' => [
						'language' => 'en',
						'value' => 'goatGloss',
					],
				],
			] ),
		] );
		$this->assertSame( 1, $result['success'] );

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'edit',
			'title' => 'Lexeme:L123',
			'undo' => $result['lastrevid'],
		] );
		$this->assertEquals( 'Success', $result['edit']['result'] );

		$this->assertTrue(
			$expected->equals(
				WikibaseRepo::getStore()
					->getEntityLookup( Store::LOOKUP_CACHING_DISABLED )
					->getEntity( $expected->getId() )
			)
		);
	}

}
