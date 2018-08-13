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
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Lexeme\Tests\MediaWiki\NonTempTableTestCase;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Tests\NewStatement;
use Wikibase\Repo\WikibaseRepo;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class LexemeSpecialWhatLinksHereTest extends SpecialPageTestBase {

	use HamcrestPHPUnitIntegration;

	/*
	 * This is needed as a workaround to avoid a MySQL temporary table error on the self-join in
	 * SpecialWhatLinksHere::showIndirectLinks()
	 */
	use NonTempTableTestCase;

	const LEXEME_ID = 'L123';

	const LANGUAGE_ID = 'Q123';

	const LEXICAL_CATEGORY_ID = 'Q321';

	const FIRST_GRAMMATICAL_FEATURE_ID = 'Q234';
	const SECOND_GRAMMATICAL_FEATURE_ID = 'Q432';

	const FIRST_STATEMENT_VALUE_ID = 'Q345';
	const SECOND_STATEMENT_VALUE_ID = 'Q543';

	protected function newSpecialPage() {
		return new SpecialWhatLinksHere();
	}

	public function testLexemeLanguage() {
		$this->saveItem( self::LANGUAGE_ID );
		$this->saveLexemeToDb();

		$output = $this->getWhatLinksHereOutputForItem( self::LANGUAGE_ID );

		$this->assertContainsLexemeLink( $output );
	}

	public function testLexemeLexicalCategory() {
		$this->saveItem( self::LEXICAL_CATEGORY_ID );
		$this->saveLexemeToDb();

		$output = $this->getWhatLinksHereOutputForItem( self::LEXICAL_CATEGORY_ID );

		$this->assertContainsLexemeLink( $output );
	}

	public function testGrammaticalFeatures() {
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

	public function testFormStatements() {
		$this->saveItem( self::FIRST_STATEMENT_VALUE_ID );
		$this->saveLexemeToDb();

		$this->assertContainsLexemeLink(
			$this->getWhatLinksHereOutputForItem( self::FIRST_STATEMENT_VALUE_ID )
		);
	}

	public function testSenseStatements() {
		$this->saveItem( self::SECOND_STATEMENT_VALUE_ID );
		$this->saveLexemeToDb();

		$this->assertContainsLexemeLink(
			$this->getWhatLinksHereOutputForItem( self::SECOND_STATEMENT_VALUE_ID )
		);
	}

	private function saveLexemeToDb() {
		$this->getEntityStore()->saveEntity(
			NewLexeme::havingId( self::LEXEME_ID )
				->withLanguage( self::LANGUAGE_ID )
				->withLexicalCategory( self::LEXICAL_CATEGORY_ID )
				->withForm( NewForm::havingId( 'F1' )
					->andGrammaticalFeature( self::FIRST_GRAMMATICAL_FEATURE_ID )
					->andStatement( NewStatement::forProperty( 'P1' )
						->withValue( new ItemId( self::FIRST_STATEMENT_VALUE_ID ) ) ) )
				->withForm( NewForm::havingId( 'F2' )
					->andGrammaticalFeature( self::SECOND_GRAMMATICAL_FEATURE_ID ) )
				->withSense( NewSense::havingId( 'S1' )
					->withStatement( NewStatement::forProperty( 'P1' )
						->withValue( new ItemId( self::SECOND_STATEMENT_VALUE_ID ) ) ) )
				->build(),
			self::class,
			$this->getUser()
		);
	}

	private function saveItem( $id ) {
		$this->getEntityStore()->saveEntity(
			new Item( new ItemId( $id ) ),
			__METHOD__,
			$this->getUser()
		);
	}

	/**
	 * @return EntityStore
	 */
	private function getEntityStore() {
		$this->tablesUsed[] = 'page';
		return WikibaseRepo::getDefaultInstance()->getEntityStore();
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
