<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Diff\LexemeDiff;
use Wikibase\Lexeme\Domain\Diff\LexemeDiffer;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\ErisGenerators\ErisTest;
use Wikibase\Lexeme\Tests\ErisGenerators\WikibaseLexemeGenerators;

/**
 * @covers \Wikibase\Lexeme\Domain\Diff\LexemeDiffer
 *
 * @license GPL-2.0-or-later
 */
class LexemeDifferTest extends TestCase {

	use ErisTest;

	public function testGivenTwoEmptyLexemes_emptyLexemeDiffIsReturned() {
		$differ = new LexemeDiffer();

		$diff = $differ->diffEntities( new Lexeme(), new Lexeme() );

		$this->assertInstanceOf( EntityDiff::class, $diff );
		$this->assertTrue( $diff->isEmpty() );
	}

	public function testClaimsAreDiffed() {
		$firstLexeme = new Lexeme();

		$secondLexeme = new Lexeme();
		$secondLexeme->getStatements()->addNewStatement(
			new PropertySomeValueSnak( 42 ),
			null,
			null,
			'guid'
		);

		$differ = new LexemeDiffer();
		$diff = $differ->diffLexemes( $firstLexeme, $secondLexeme );

		$this->assertCount( 1, $diff->getClaimsDiff()->getAdditions() );
	}

	public function testLemmasAreDiffed() {
		$firstLexeme = new Lexeme();

		$secondLexeme = new Lexeme();
		$secondLexeme->setLemmas( new TermList( [ new Term( 'es', 'Mexico city' ) ] ) );

		$differ = new LexemeDiffer();
		$diff = $differ->diffLexemes( $firstLexeme, $secondLexeme );

		/** @var LexemeDiff $diff */
		$this->assertCount( 1, $diff->getLemmasDiff()->getAdditions() );
	}

	public function testLexicalCategoryIsDiffed() {
		$firstLexeme = new Lexeme();

		$secondLexeme = new Lexeme();
		$secondLexeme->setLexicalCategory( new ItemId( 'Q33' ) );

		$differ = new LexemeDiffer();
		$diff = $differ->diffLexemes( $firstLexeme, $secondLexeme );

		/** @var LexemeDiff $diff */
		$this->assertCount( 1, $diff->getLexicalCategoryDiff()->getAdditions() );
	}

	public function testLanguageIsDiffed() {
		$firstLexeme = new Lexeme();

		$secondLexeme = new Lexeme();
		$secondLexeme->setLanguage( new ItemId( 'Q11' ) );

		$differ = new LexemeDiffer();
		$diff = $differ->diffLexemes( $firstLexeme, $secondLexeme );

		/** @var LexemeDiff $diff */
		$this->assertCount( 1, $diff->getLanguageDiff()->getAdditions() );
	}

	public function testGivenEmptyLexeme_constructionDiffIsEmpty() {
		$differ = new LexemeDiffer();
		$this->assertTrue( $differ->getConstructionDiff( new Lexeme() )->isEmpty() );
	}

	public function testGivenEmptyLexeme_destructionDiffIsEmpty() {
		$differ = new LexemeDiffer();
		$this->assertTrue( $differ->getDestructionDiff( new Lexeme() )->isEmpty() );
	}

	public function testCanDiffEntityType() {
		$differ = new LexemeDiffer();
		$this->assertTrue( $differ->canDiffEntityType( Lexeme::ENTITY_TYPE ) );
		$this->assertFalse( $differ->canDiffEntityType( Item::ENTITY_TYPE ) );
		$this->assertFalse( $differ->canDiffEntityType( Property::ENTITY_TYPE ) );
	}

	public function testProperty_LexemeDiffToArrayAlwaysThrowsAnException() {
		$differ = new LexemeDiffer();

		/**
		 * This property is needed to avoid serializing currently needless data in
		 * \Wikibase\Lib\Changes\EntityChange::getSerializedInfo.
		 * Making `toArray()` to always return an empty array was the easiest way to achieve this.
		 */

		$this->eris()
			->limitTo( 2 )
			->forAll(
				WikibaseLexemeGenerators::lexeme( new LexemeId( 'L1' ) ),
				WikibaseLexemeGenerators::lexeme( new LexemeId( 'L1' ) )
			)
			->then( function ( Lexeme $lexeme1, Lexeme $lexeme2 ) use ( $differ ) {
				$patch = $differ->diffEntities( $lexeme1, $lexeme2 );
				$this->expectException( \LogicException::class );
				$patchAsArray = $patch->toArray();

				$this->assertEquals( [], $patchAsArray );
			} );
	}

}
