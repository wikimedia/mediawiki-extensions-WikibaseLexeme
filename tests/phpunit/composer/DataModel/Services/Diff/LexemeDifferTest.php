<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiffer;

/**
 * @covers Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiffer
 *
 * @license GPL-2.0+
 */
class LexemeDifferTest extends PHPUnit_Framework_TestCase {
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

}
