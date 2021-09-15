<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lexeme\Domain\Diff\SenseDiffer;
use Wikibase\Lexeme\Domain\Diff\SensePatcher;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Tests\ErisGenerators\ErisTest;
use Wikibase\Lexeme\Tests\ErisGenerators\WikibaseLexemeGenerators;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;

/**
 * @covers \Wikibase\Lexeme\Domain\Diff\SenseDiffer
 * @covers \Wikibase\Lexeme\Domain\Diff\SensePatcher
 *
 * @license GPL-2.0-or-later
 */
class SenseDifferPatcherTest extends TestCase {

	use ErisTest;

	public function testProperty_PatchingLexemeWithGeneratedDiffAlwaysRestoresItToTheTargetState() {
		$differ = new SenseDiffer();
		$patcher = new SensePatcher();

		// Line below is needed to reproduce failures. In case of failure seed will be in the output
		//$this->eris()->seed(1504876177284329)->forAll( ...

		$this->eris()
			->forAll(
				WikibaseLexemeGenerators::sense( new SenseId( 'L1-S1' ) ),
				WikibaseLexemeGenerators::sense( new SenseId( 'L1-S1' ) )
			)
			->then( function ( Sense $sense1, Sense $sense2 ) use ( $differ, $patcher ) {
				$patch = $differ->diffEntities( $sense1, $sense2 );
				$patcher->patchEntity( $sense1, $patch );

				$this->assertEquals( $sense1, $sense2 );
			} );
	}

	public function testDiffAndPatchCanChangeRepresentations() {
		$differ = new SenseDiffer();
		$patcher = new SensePatcher();
		$sense1 = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'cat' )
			->build();
		$sense2 = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'goat' )
			->build();

		$diff = $differ->diffEntities( $sense1, $sense2 );
		$patcher->patchEntity( $sense1, $diff );

		$this->assertEquals( $sense2, $sense1 );
	}

	public function testDiffAndPatchCanAtomicallyChangeRepresentations() {
		$differ = new SenseDiffer();
		$patcher = new SensePatcher();
		$sense1 = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'en-value' )
			->build();
		$sense2 = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'en-value' )
			->withGloss( 'fr', 'fr-value' )
			->build();
		$latestSense = NewSense::havingId( 'S1' )
			->withGloss( 'de', 'de-value' )
			->build();

		$diff = $differ->diffEntities( $sense1, $sense2 );
		$patcher->patchEntity( $latestSense, $diff );

		$this->assertEquals(
			'fr-value',
			$latestSense->getGlosses()->getByLanguage( 'fr' )->getText()
		);
		$this->assertEquals(
			'de-value',
			$latestSense->getGlosses()->getByLanguage( 'de' )->getText()
		);
	}

	public function testDiffAndPatchCanChangeStatements() {
		$differ = new SenseDiffer();
		$patcher = new SensePatcher();
		$sense1 = NewSense::havingId( 'S1' )
			->withStatement( $this->someStatement( 'P1', 'guid1' ) )
			->build();
		$sense2 = NewSense::havingId( 'S1' )
			->withStatement( $this->someStatement( 'P1', 'guid1' ) )
			->withStatement( $this->someStatement( 'P2', 'guid2' ) )
			->build();
		$latestSense = NewSense::havingId( 'S1' )
			->withStatement( $this->someStatement( 'P3', 'guid3' ) )
			->build();

		$diff = $differ->diffEntities( $sense1, $sense2 );
		$patcher->patchEntity( $latestSense, $diff );

		$this->assertNotNull( $latestSense->getStatements()->getFirstStatementWithGuid( 'guid3' ) );
		$this->assertNotNull( $latestSense->getStatements()->getFirstStatementWithGuid( 'guid2' ) );
		$this->assertNull( $latestSense->getStatements()->getFirstStatementWithGuid( 'guid1' ) );
	}

	/**
	 * @return mixed
	 */
	private function someStatement( $propertyId, $guid ) {
		$statement = new Statement(
			new PropertySomeValueSnak( new NumericPropertyId( $propertyId ) )
		);
		$statement->setGuid( $guid );
		return $statement;
	}

}
