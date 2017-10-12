<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use User;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Repo\Tests\Api\WikibaseApiTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\DataModel\Serialization\ExternalLexemeSerializer
 *
 * @license GPL-2.0+
 *
 * @group Database
 * @group medium
 * @group WikibaseLexeme
 */
class LexemeGetEntitiesTest extends WikibaseApiTestCase {

	const LEXEME_ID = 'L1200';

	public function testNextFormIdIsNotIncludedInLexemeData() {
		$this->saveDummyLexemeToDatabase();

		list ( $result, ) = $this->doApiRequest( [
			'action' => 'wbgetentities',
			'ids' => self::LEXEME_ID,
		] );

		$this->assertArrayNotHasKey( 'nextFormId', $result['entities'][self::LEXEME_ID] );
	}

	private function saveDummyLexemeToDatabase() {
		$lexeme = new Lexeme(
			new LexemeId( self::LEXEME_ID ),
			new TermList( [
				new Term( 'en', 'goat' ),
			] ),
			new ItemId( 'Q303' ),
			new ItemId( 'Q808' )
		);

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$store->saveEntity( $lexeme, self::class, $this->getMock( User::class ) );
	}

}
