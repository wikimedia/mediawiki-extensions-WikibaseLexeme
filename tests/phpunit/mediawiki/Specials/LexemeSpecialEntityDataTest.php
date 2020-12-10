<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials;

use FauxRequest;
use FauxResponse;
use SpecialPage;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Specials\SpecialEntityData;
use Wikibase\Repo\WikibaseRepo;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class LexemeSpecialEntityDataTest extends \SpecialPageTestBase {

	private const LEXEME_ID = 'L1222';

	/** @var EntityStore */
	private $entityStore;

	protected function setUp() : void {
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
		return $this->getServiceContainer()
			->getSpecialPageFactory()
			->getPage( SpecialEntityData::SPECIAL_PAGE_NAME );
	}

	private function saveLexemeToDb() {
		$this->tablesUsed[] = 'page';
		$this->entityStore->saveEntity(
			NewLexeme::havingId( self::LEXEME_ID )->build(),
			self::class,
			$this->getTestUser()->getUser()
		);
	}

	public function testSensesKeyExistsInJson() {
		$this->saveLexemeToDb();
		$params = [ 'id' => self::LEXEME_ID, 'format' => 'json' ];
		$request = new FauxRequest( $params );
		$request->setRequestURL( $this->newSpecialPage()->getPageTitle()->getLocalURL( $params ) );

		/** @var FauxResponse $response */
		list( $output, $response ) = $this->executeSpecialPage(
			'',
			$request
		);

		$resultArray = json_decode( $output, true );
		$this->assertArrayHasKey( 'senses', $resultArray['entities'][self::LEXEME_ID] );
	}

}
