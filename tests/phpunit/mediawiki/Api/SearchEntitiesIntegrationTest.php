<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use User;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;

/**
 * @covers \Wikibase\Repo\Api\SearchEntities
 *
 * @license GPL-2.0-or-later
 *
 * @group API
 * @group WikibaseAPI
 * @group Database
 * @group medium
 */
class SearchEntitiesIntegrationTest extends WikibaseLexemeApiTestCase {

	const LEXEME_ID = 'L100';

	public function testLexemeIsFoundWhenIdGivenAsSearchTerm() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbsearchentities',
			'search' => self::LEXEME_ID,
			'type' => 'lexeme',
			'language' => 'en',
		];

		list( $result, ) = $this->doApiRequest( $params );

		$this->assertCount( 1, $result['search'] );
		$this->assertSame( self::LEXEME_ID, $result['search'][0]['id'] );
		$this->assertSame(
			[ 'type' => 'entityId', 'text' => self::LEXEME_ID ],
			$result['search'][0]['match']
		);
	}

	public function testGivenIdOfNonExistingLexeme_noResults() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbsearchentities',
			'search' => 'L21323232',
			'type' => 'lexeme',
			'language' => 'en',
		];

		list( $result, ) = $this->doApiRequest( $params );

		$this->assertEmpty( $result['search'] );
	}

	private function saveDummyLexemeToDatabase() {
		$this->entityStore->saveEntity(
			$this->getDummyLexeme(),
			self::class,
			$this->getMock( User::class )
		);
	}

	private function getDummyLexeme() {
		return NewLexeme::havingId( self::LEXEME_ID )->build();
	}

}
