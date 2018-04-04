<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Content;

use Diff\DiffOp\Diff\Diff;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Content\LexemeContent;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiff;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiffer;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\Content\EntityContentDiff;

/**
 * @covers \Wikibase\Lexeme\Content\LexemeContent
 *
 * @license GPL-2.0-or-later
 */
class LexemeContentTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testInvalidEntityType() {
		$this->setExpectedException( InvalidArgumentException::class );
		new LexemeContent( new EntityInstanceHolder( new Item() ) );
		$this->assertTrue( true ); // Don't mark as risky
	}

	public function testGetEntity() {
		$lexeme = new Lexeme( new LexemeId( 'L1' ) );
		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );

		$this->assertSame( $lexeme, $lexemeContent->getEntity() );
	}

	/**
	 * @dataProvider countableLexemeProvider
	 */
	public function testIsCountable( $lexeme ) {
		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );
		$this->assertTrue( $lexemeContent->isCountable() );
	}

	public function countableLexemeProvider() {
		$countable = [];

		$lexeme = new Lexeme( new LexemeId( 'L1' ) );
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );
		$countable[] = [ $lexeme ];

		return $countable;
	}

	public function testNotCountable() {
		$lexemeContent = new LexemeContent( new EntityInstanceHolder(
			new Lexeme( new LexemeId( 'L1' ) )
		) );
		$this->assertFalse( $lexemeContent->isCountable() );
	}

	public function testIsValid() {
		$lexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'test' ) ] ),
			new ItemId( 'Q120' ),
			new ItemId( 'Q121' )
		);

		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );
		$this->assertTrue( $lexemeContent->isValid() );
	}

	/**
	 * @dataProvider provideInvalidLexeme
	 */
	public function testNotValid( $lexeme ) {
		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );
		$this->assertFalse( $lexemeContent->isValid() );
	}

	public function provideInvalidLexeme() {
		yield [ new Lexeme( new LexemeId( 'L1' ) ) ];
		yield [ new Lexeme( new LexemeId( 'L1' ), null, new ItemId( 'Q120' ), new ItemId( 'Q121' ) ) ];
		yield [ new Lexeme( new LexemeId( 'L1' ), null, null, new ItemId( 'Q121' ) ) ];
		yield [ new Lexeme( new LexemeId( 'L1' ), null, new ItemId( 'Q120' ) ) ];
	}

	/**
	 * @dataProvider provideGetPatchedCopy
	 */
	public function testGetPatchedCopy( Lexeme $lexeme, LexemeDiff $lexemeDiff, $assertions ) {
		$content = new LexemeContent( new EntityInstanceHolder( $lexeme ) );
		$contentDiff = new EntityContentDiff( $lexemeDiff, new Diff( [] ), $lexeme->getType() );
		$patchedCopy = $content->getPatchedCopy( $contentDiff );
		$patchedEntity = $patchedCopy->getEntity();
		$assertions( $lexeme, $patchedEntity );
	}

	public function provideGetPatchedCopy() {
		$lexemeDiffer = new LexemeDiffer();

		$newLexemeL1 = NewLexeme::havingId( 'L1' );
		$newFormF1 = NewForm::havingId( new FormId( 'L1-F1' ) );

		$newFormF1FeatureQ1 = $newFormF1->andGrammaticalFeature( new ItemId( 'Q1' ) );
		$newFormF1FeatureQ2 = $newFormF1->andGrammaticalFeature( new ItemId( 'Q2' ) );
		$newFormF1FeatureQ1andQ2 = $newFormF1->andGrammaticalFeature( new ItemId( 'Q1' ) )
			->andGrammaticalFeature( new ItemId( 'Q2' ) );

		// Build forms for use throughout that will have the same automatic representation added
		$formF1FeatureQ1 = $newFormF1FeatureQ1->build();
		$formF1FeatureQ2 = $newFormF1FeatureQ2->build();
		$formF1FeatureQ1andQ2 = $newFormF1FeatureQ1andQ2->build();

		$lexemeEmpty = $newLexemeL1->build();
		$lexemeFormFeatureQ1 = $newLexemeL1->withForm( $formF1FeatureQ1 )->build();
		$lexemeFormFeatureQ2 = $newLexemeL1->withForm( $formF1FeatureQ2 )->build();
		$lexemeFormFeatureQ1andQ2 = $newLexemeL1->withForm( $formF1FeatureQ1andQ2 )->build();

		yield 'Minimal entities, empty diff, should still be empty' => [
			$lexemeEmpty,
			$lexemeDiffer->diffEntities( $lexemeEmpty, $lexemeEmpty ),
			function ( Lexeme $lexeme, Lexeme $lexemeCopy ) {
				$this->assertTrue( $lexemeCopy->equals( $lexeme ) );
			},
		];
		yield 'Entities with the same form, empty diff, should remain unchanged' => [
			$lexemeFormFeatureQ1,
			$lexemeDiffer->diffEntities( $lexemeFormFeatureQ1, $lexemeFormFeatureQ1 ),
			function ( Lexeme $lexeme, Lexeme $lexemeCopy ) {
				$this->assertTrue( $lexemeCopy->equals( $lexeme ) );
			},
		];
		yield 'Adding a form feature (Q2)' => [
			$lexemeFormFeatureQ1,
			$lexemeDiffer->diffEntities( $lexemeFormFeatureQ1, $lexemeFormFeatureQ1andQ2 ),
			function ( Lexeme $lexeme, Lexeme $lexemeCopy ) use ( $lexemeFormFeatureQ1andQ2 ) {
				$this->assertFalse( $lexemeCopy->equals( $lexeme ) );
				$this->assertTrue( $lexemeCopy->equals( $lexemeFormFeatureQ1andQ2 ) );
			},
		];
		yield 'Removing a form feature (Q2)' => [
			$lexemeFormFeatureQ1andQ2,
			$lexemeDiffer->diffEntities( $lexemeFormFeatureQ1andQ2, $lexemeFormFeatureQ1 ),
			function ( Lexeme $lexeme, Lexeme $lexemeCopy ) use ( $lexemeFormFeatureQ1 ) {
				$this->assertFalse( $lexemeCopy->equals( $lexeme ) );
				$this->assertTrue( $lexemeCopy->equals( $lexemeFormFeatureQ1 ) );
			},
		];
		yield 'Changing a form feature (Q1 -> Q2)' => [
			$lexemeFormFeatureQ1,
			$lexemeDiffer->diffEntities( $lexemeFormFeatureQ1, $lexemeFormFeatureQ2 ),
			function ( Lexeme $lexeme, Lexeme $lexemeCopy ) use ( $lexemeFormFeatureQ2 ) {
				$this->assertFalse( $lexemeCopy->equals( $lexeme ) );
				$this->assertTrue( $lexemeCopy->equals( $lexemeFormFeatureQ2 ) );
			},
		];
	}

}
