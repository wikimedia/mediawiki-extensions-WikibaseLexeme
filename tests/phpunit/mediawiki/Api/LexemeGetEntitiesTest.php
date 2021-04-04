<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiMain;
use ApiResult;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;

/**
 * @covers \Wikibase\Lexeme\Serialization\ExternalLexemeSerializer
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class LexemeGetEntitiesTest extends WikibaseLexemeApiTestCase {

	private const LEXEME_ID = 'L1200';

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

	public function testEmptyListMetaData() {
		$this->entityStore->saveEntity(
			NewLexeme::havingId( self::LEXEME_ID )
				->withForm( NewForm::any()->andId( 'F1' ) )
				->withSense( NewSense::havingId( 'S1' )->withGloss( 'en', 'foo' ) )
				->build(),
			self::class,
			$this->getTestUser()->getUser()
		);

		$repoSettings = clone $this->getServiceContainer()->getService( 'WikibaseRepo.Settings' );
		$repoSettings->setSetting( 'tmpSerializeEmptyListsAsObjects', true );
		$this->setService( 'WikibaseRepo.Settings', $repoSettings );

		/** @var ApiMain $module */
		$module = $this->doApiRequest(
			[
				'action' => 'wbgetentities',
				'format' => 'json',
				'ids' => self::LEXEME_ID,
			],
			null,
			true
		)[3];

		// avoid loadEntity which strips the metadata
		$lexemeData = $module->getResult()->getResultData()['entities'][self::LEXEME_ID];

		$this->assertArrayHasKey( ApiResult::META_TYPE, $lexemeData['claims'] );
		$this->assertEquals( 'kvp', $lexemeData['claims'][ApiResult::META_TYPE] );

		$this->assertArrayHasKey( ApiResult::META_TYPE, $lexemeData['forms'][0]['claims'] );
		$this->assertEquals( 'kvp', $lexemeData['forms'][0]['claims'][ApiResult::META_TYPE] );

		$this->assertArrayHasKey( ApiResult::META_TYPE, $lexemeData['senses'][0]['claims'] );
		$this->assertEquals( 'kvp', $lexemeData['senses'][0]['claims'][ApiResult::META_TYPE] );
	}

}
