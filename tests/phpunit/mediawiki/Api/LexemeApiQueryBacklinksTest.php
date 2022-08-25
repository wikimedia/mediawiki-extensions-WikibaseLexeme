<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Repo\WikibaseRepo;

/**
 * E2E test for backlinks/WhatLinksHere functionality for lexeme specific properties
 *
 * @coversNothing
 *
 * @group medium
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class LexemeApiQueryBacklinksTest extends WikibaseLexemeApiTestCase {

	private const LANGUAGE_ID = 'Q123';
	private const LEXICAL_CATEGORY_ID = 'Q321';
	private const GRAMMATICAL_FEATURE_1_ID = 'Q234';
	private const GRAMMATICAL_FEATURE_2_ID = 'Q432';
	private const FORM_STATEMENT_VALUE = 'Q789';
	private const SENSE_STATEMENT_VALUE = 'Q987';

	public function testLexemeLanguage() {
		$lexeme = $this->saveTestLexemeToDb();

		$this->assertHasLexemeBacklink(
			$lexeme,
			new ItemId( self::LANGUAGE_ID )
		);
	}

	public function testLexemeLexicalCategory() {
		$lexeme = $this->saveTestLexemeToDb();

		$this->assertHasLexemeBacklink(
			$lexeme,
			new ItemId( self::LEXICAL_CATEGORY_ID )
		);
	}

	public function testGrammaticalFeatures() {
		$lexeme = $this->saveTestLexemeToDb();

		$this->assertHasLexemeBacklink(
			$lexeme,
			new ItemId( self::GRAMMATICAL_FEATURE_1_ID )
		);
		$this->assertHasLexemeBacklink(
			$lexeme,
			new ItemId( self::GRAMMATICAL_FEATURE_2_ID )
		);
	}

	public function testFormStatements() {
		$lexeme = $this->saveTestLexemeToDb();

		$this->assertHasLexemeBacklink(
			$lexeme,
			new ItemId( self::FORM_STATEMENT_VALUE )
		);
	}

	public function testSenseStatements() {
		$lexeme = $this->saveTestLexemeToDb();

		$this->assertHasLexemeBacklink(
			$lexeme,
			new ItemId( self::SENSE_STATEMENT_VALUE )
		);
	}

	/**
	 * @param Lexeme $lexeme The lexeme that is linked to
	 * @param EntityId $entityId The entity supposedly linking to the lexeme
	 */
	private function assertHasLexemeBacklink( Lexeme $lexeme, EntityId $entityId ) {
		$result = $this->doBacklinksRequestForEntity( $entityId );

		$entityTitle = $this->getEntityTitle( $lexeme->getId() );
		$hasBacklink = false;

		foreach ( $result['query']['pages'] as $link ) {
			if ( $link['title'] === $entityTitle ) {
				$hasBacklink = true;
				break;
			}
		}

		$this->assertTrue( $hasBacklink, "API response contains '$entityTitle'." );
	}

	private function getEntityTitle( EntityId $id ) {
		return WikibaseRepo::getEntityTitleLookup()
			->getTitleForId( $id )
			->getPrefixedText();
	}

	private function saveTestLexemeToDb() {
		$p4711 = new Property( new NumericPropertyId( 'P4711' ), null, 'wikibase-item' );
		$this->saveEntity( $p4711 );

		$language = NewItem::withId( self::LANGUAGE_ID )->build();
		$this->saveEntity( $language );

		$lexCat = NewItem::withId( self::LEXICAL_CATEGORY_ID )->build();
		$this->saveEntity( $lexCat );

		$gf1 = NewItem::withId( self::GRAMMATICAL_FEATURE_1_ID )->build();
		$gf2 = NewItem::withId( self::GRAMMATICAL_FEATURE_2_ID )->build();
		$this->saveEntity( $gf1 );
		$this->saveEntity( $gf2 );

		$formStatementValueItem = NewItem::withId( self::FORM_STATEMENT_VALUE )->build();
		$this->saveEntity( $formStatementValueItem );

		$senseStatementValueItem = NewItem::withId( self::SENSE_STATEMENT_VALUE )->build();
		$this->saveEntity( $senseStatementValueItem );

		$lexeme = NewLexeme::havingId( 'L123' )
			->withLanguage( $language->getId() )
			->withLexicalCategory( $lexCat->getId() )
			->withForm( NewForm::havingId( 'F1' )
				->andGrammaticalFeature( $gf1->getId() )
				->andStatement( NewStatement::forProperty( $p4711->getId() )
					->withValue( $formStatementValueItem->getId() ) ) )
			->withForm( NewForm::havingId( 'F2' )
				->andGrammaticalFeature( $gf2->getId() ) )
			->withSense( NewSense::havingId( 'S1' )
				->withStatement( NewStatement::forProperty( $p4711->getId() )
					->withValue( $senseStatementValueItem->getId() ) ) )
			->build();

		$this->saveEntity( $lexeme );

		return $lexeme;
	}

	private function doBacklinksRequestForEntity( EntityId $id ) {
		list( $result ) = $this->doApiRequest( [
			'action' => 'query',
			'generator' => 'backlinks',
			'gbltitle' => $this->getEntityTitle( $id ),
		] );

		return $result;
	}

}
