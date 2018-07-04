<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials;

use FauxRequest;
use HamcrestPHPUnitIntegration;
use SpecialPageTestBase;
use SpecialWhatLinksHere;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class LexemeSpecialWhatLinksHereTest extends SpecialPageTestBase {

	use HamcrestPHPUnitIntegration;

	const LEXEME_ID = 'L123';

	const LANGUAGE_ID = 'Q123';

	const LEXICAL_CATEGORY_ID = 'Q321';

	const FIRST_GRAMMATICAL_FEATURE_ID = 'Q234';
	const SECOND_GRAMMATICAL_FEATURE_ID = 'Q432';

	/** @var EntityStore */
	private $entityStore;

	public function setUp() {
		parent::setUp();

		$repo = WikibaseRepo::getDefaultInstance();
		$this->entityStore = $repo->getEntityStore();
	}

	protected function newSpecialPage() {
		return new SpecialWhatLinksHere();
	}

	public function testLexemeLanguage() {
		$this->skipMysql();
		$this->saveItem( self::LANGUAGE_ID );
		$this->saveLexemeToDb();

		$output = $this->getWhatLinksHereOutputForItem( self::LANGUAGE_ID );

		$this->assertContainsLexemeLink( $output );
	}

	public function testLexemeLexicalCategory() {
		$this->skipMysql();
		$this->saveItem( self::LEXICAL_CATEGORY_ID );
		$this->saveLexemeToDb();

		$output = $this->getWhatLinksHereOutputForItem( self::LEXICAL_CATEGORY_ID );

		$this->assertContainsLexemeLink( $output );
	}

	public function testGrammaticalFeatures() {
		$this->skipMysql();
		$this->saveItem( self::FIRST_GRAMMATICAL_FEATURE_ID );
		$this->saveItem( self::SECOND_GRAMMATICAL_FEATURE_ID );
		$this->saveLexemeToDb();

		$this->assertContainsLexemeLink(
			$this->getWhatLinksHereOutputForItem( self::FIRST_GRAMMATICAL_FEATURE_ID )
		);
		$this->assertContainsLexemeLink(
			$this->getWhatLinksHereOutputForItem( self::SECOND_GRAMMATICAL_FEATURE_ID )
		);
	}

	private function saveLexemeToDb() {
		$this->entityStore->saveEntity(
			NewLexeme::havingId( self::LEXEME_ID )
				->withLanguage( self::LANGUAGE_ID )
				->withLexicalCategory( self::LEXICAL_CATEGORY_ID )
				->withForm( NewForm::havingId( 'F1' )
					->andGrammaticalFeature( self::FIRST_GRAMMATICAL_FEATURE_ID ) )
				->withForm( NewForm::havingId( 'F2' )
					->andGrammaticalFeature( self::SECOND_GRAMMATICAL_FEATURE_ID ) )
				->build(),
			self::class,
			$this->getTestUser()->getUser()
		);
	}

	private function saveItem( $id ) {
		$this->entityStore->saveEntity(
			new Item( new ItemId( $id ) ),
			__METHOD__,
			$this->getTestUser()->getUser()
		);
	}

	/**
	 * https://dev.mysql.com/doc/refman/8.0/en/temporary-table-problems.html
	 */
	private function skipMysql() {
		if ( $this->db->getType() === 'mysql' && $this->usesTemporaryTables() ) {
			$this->markTestSkipped( 'MySQL doesn\'t support self-joins on temporary tables' );
		}
	}

	private function assertContainsLexemeLink( $output ) {
		$this->assertThatHamcrest( $output, is( htmlPiece( havingChild(
			both( tagMatchingOutline( '<a title="Lexeme:' . self::LEXEME_ID . '"/>' ) )
				->andAlso( havingTextContents( 'Lexeme:' . self::LEXEME_ID ) )
		) ) ) );
	}

	private function getWhatLinksHereOutputForItem( $id ) {
		list( $output, $reponse ) = $this->executeSpecialPage(
			'Item:' . $id,
			new FauxRequest()
		);

		return $output;
	}

}
