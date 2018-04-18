<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials;

use FauxRequest;
use FauxResponse;
use SpecialPage;
use User;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Specials\SpecialEntityData;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class LexemeSpecialEntityDataTest extends \SpecialPageTestBase {

	const LEXEME_ID = 'L1222';

	/** @var EntityStore */
	private $entityStore;

	public function setUp() {
		parent::setUp();

		$repo = WikibaseRepo::getDefaultInstance();
		$this->entityStore = $repo->getEntityStore();
	}

	/**
	 * Returns a new instance of the special page under test.
	 *
	 * @return SpecialPage
	 */
	protected function newSpecialPage() {
		return new SpecialEntityData();
	}

	private function saveLexemeToDb() {
		$this->entityStore->saveEntity(
			NewLexeme::havingId( self::LEXEME_ID )->build(),
			self::class,
			$this->getMock( User::class )
		);
	}

	public function testSensesKeyExistsInJsonWhenEnabled() {
		$this->setMwGlobals( 'wgLexemeEnableSenses', true );
		$this->saveLexemeToDb();

		/** @var FauxResponse $response */
		list( $output, $response ) = $this->executeSpecialPage(
			'',
			new FauxRequest( [ 'id' => self::LEXEME_ID, 'format' => 'json' ] )
		);

		$resultArray = json_decode( $output, true );
		$this->assertArrayHasKey( 'senses', $resultArray['entities'][self::LEXEME_ID] );
	}

	public function testSensesKeyDoesntExistInJsonWhenDisabled() {
		$this->setMwGlobals( 'wgLexemeEnableSenses', false );
		$this->saveLexemeToDb();

		/** @var FauxResponse $response */
		list( $output, $response ) = $this->executeSpecialPage(
			'',
			new FauxRequest( [ 'id' => self::LEXEME_ID, 'format' => 'json' ] )
		);

		$resultArray = json_decode( $output, true );
		$this->assertArrayNotHasKey( 'senses', $resultArray['entities'][self::LEXEME_ID] );
	}

}
