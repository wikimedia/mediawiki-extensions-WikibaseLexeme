<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;

/**
 * @covers \Wikibase\Lexeme\Serialization\ExternalLexemeSerializer
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class LexemeGetEntitiesTest extends WikibaseLexemeApiTestCase {

	const LEXEME_ID = 'L1200';

	public function testNextFormIdIsNotIncludedInLexemeData() {
		$this->saveDummyLexemeToDatabase();

		list( $result, ) = $this->doApiRequest( [
			'action' => 'wbgetentities',
			'ids' => self::LEXEME_ID,
		] );

		$this->assertArrayNotHasKey( 'nextFormId', $result['entities'][self::LEXEME_ID] );
	}

	public function testGivenIdOfExistingLexemeWithForm_formIsContainedInGetEntity() {
		$this->entityStore->saveEntity(
			NewLexeme::havingId( self::LEXEME_ID )->withForm( NewForm::any() )->build(),
			self::class,
			$this->getTestUser()->getUser()
		);

		$lexemeData = $this->loadEntity( self::LEXEME_ID );

		$this->assertCount( 1, $lexemeData['forms'] );
	}

	public function testSensesKeyExists() {
		$this->entityStore->saveEntity(
			NewLexeme::havingId( self::LEXEME_ID )->build(),
			self::class,
			$this->getTestUser()->getUser()
		);

		$lexemeData = $this->loadEntity( self::LEXEME_ID );

		$this->assertArrayHasKey( 'senses', $lexemeData );
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

		$this->entityStore->saveEntity(
			$lexeme,
			self::class,
			$this->getTestUser()->getUser()
		);
	}

	public function testGettingSense() {
		$this->entityStore->saveEntity(
			NewLexeme::havingId( self::LEXEME_ID )
				->withSense( NewSense::havingId( 'S1' )->withGloss( 'en', 'foo' ) )
				->build(),
			self::class,
			$this->getTestUser()->getUser()
		);

		$senseData = $this->loadEntity( self::LEXEME_ID . '-S1' );

		$this->assertEquals( self::LEXEME_ID . '-S1', $senseData['id'] );
		$this->assertEquals( [ 'language' => 'en', 'value' => 'foo' ], $senseData['glosses']['en'] );
	}

}
