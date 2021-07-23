<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials;

use FauxRequest;
use FauxResponse;
use SpecialPage;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
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

	protected function setUp(): void {
		parent::setUp();

		$this->entityStore = WikibaseRepo::getEntityStore();
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

	/**
	 * @param Lexeme|NewLexeme|null $lexeme
	 */
	private function saveLexemeToDb( $lexeme = null ) {
		if ( $lexeme === null ) {
			$lexeme = NewLexeme::havingId( self::LEXEME_ID );
		}
		if ( $lexeme instanceof NewLexeme ) {
			$lexeme = $lexeme->build();
		}
		$this->tablesUsed[] = 'page';
		$this->entityStore->saveEntity(
			$lexeme,
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

	public function testCanLoadSense() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'a sense' );
		$lexeme = NewLexeme::havingId( self::LEXEME_ID )
			->withSense( $sense );
		$this->saveLexemeToDb( $lexeme );
		$senseId = self::LEXEME_ID . '-S1';
		$params = [ 'id' => $senseId, 'format' => 'json' ];
		$request = new FauxRequest( $params );
		$request->setRequestURL( $this->newSpecialPage()->getPageTitle()->getLocalURL( $params ) );

		[ $output ] = $this->executeSpecialPage(
			'',
			$request
		);

		$resultArray = json_decode( $output, true );
		$entityJson = $resultArray['entities'][$senseId];
		$this->assertArrayHasKey( 'glosses', $entityJson );
		$glosses = $entityJson['glosses'];
		$this->assertArrayHasKey( 'en', $glosses );
		$this->assertSame( 'a sense', $glosses['en']['value'] );
	}

	public function testCanLoadForm() {
		$form = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'a form' );
		$lexeme = NewLexeme::havingId( self::LEXEME_ID )
			->withForm( $form );
		$this->saveLexemeToDb( $lexeme );
		$formId = self::LEXEME_ID . '-F1';
		$params = [ 'id' => $formId, 'format' => 'json' ];
		$request = new FauxRequest( $params );
		$request->setRequestURL( $this->newSpecialPage()->getPageTitle()->getLocalURL( $params ) );

		[ $output ] = $this->executeSpecialPage(
			'',
			$request
		);

		$resultArray = json_decode( $output, true );
		$entityJson = $resultArray['entities'][$formId];
		$this->assertArrayHasKey( 'representations', $entityJson );
		$representations = $entityJson['representations'];
		$this->assertArrayHasKey( 'en', $representations );
		$this->assertSame( 'a form', $representations['en']['value'] );
	}

}
