<?php

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @covers \Wikibase\Lexeme\Domain\Model\Sense
 *
 * @license GPL-2.0-or-later
 */
class SenseTest extends MediaWikiUnitTestCase {

	public function testCanBeCreated() {
		$sense = new Sense( new SenseId( 'L1-S1' ), new TermList(), new StatementList() );

		$this->assertSame( 'L1-S1', $sense->getId()->getSerialization() );
		$this->assertTrue( $sense->getGlosses()->isEmpty() );
		$this->assertTrue( $sense->getStatements()->isEmpty() );
	}

	public function testCopyClones() {
		$sense = new Sense( new SenseId( 'L1-S1' ), new TermList(), new StatementList() );
		$copy = $sense->copy();

		$this->assertNotSame( $sense->getGlosses(), $copy->getGlosses() );
		$this->assertNotSame( $sense->getStatements(), $copy->getStatements() );
	}

	public function testIdCanNotBeChanged() {
		$sense = NewSense::havingId( 'S1' )->build();
		$this->expectException( LogicException::class );
		$sense->setId( new SenseId( 'L1-S2' ) );
	}

	public function testGivenSenseWithInitiallyRequiredGloss_isNotEmpty() {
		$sense = NewSense::havingGloss( 'en', 'one' )->build();
		$this->assertFalse( $sense->isEmpty() );
	}

	public function testGivenSenseWithInitiallyRequiredGlossRemoved_isEmpty() {
		$sense = NewSense::havingGloss( 'en', 'one' )->build();
		$sense->getGlosses()->removeByLanguage( 'en' );
		$this->assertTrue( $sense->isEmpty() );
	}

	public function provideNonEmptySenses() {
		return [
			'2 glosses' => [
				NewSense::havingGloss( 'en', 'one' )
					->withGloss( 'fr', 'two' )
					->build()
			],
			'1 statement' => [
				NewSense::havingStatement( $this->newStatement() )
					->build()
			],
		];
	}

	/**
	 * @dataProvider provideNonEmptySenses
	 */
	public function testGivenSenseWithOptionalElements_isNotEmpty( Sense $sense ) {
		$this->assertFalse( $sense->isEmpty() );
	}

	public function provideEqualSenses() {
		$minimal = NewSense::havingId( 'S1' )->withGloss( 'en', 'minimal' );
		$nonEmpty = $minimal->withStatement( $this->newStatement() );

		$minimalInstance = $minimal->build();

		return [
			'same instance' => [
				$minimalInstance,
				$minimalInstance
			],
			'minimal senses' => [
				$minimal->build(),
				$minimal->build()
			],
			'different IDs' => [
				$minimal->build(),
				NewSense::havingId( 'S2' )->withGloss( 'en', 'minimal' )->build()
			],
			'non-empty senses' => [
				$nonEmpty->build(),
				$nonEmpty->build()
			],
		];
	}

	/**
	 * @dataProvider provideEqualSenses
	 */
	public function testGivenEqualSenses_areEqual( Sense $sense1, Sense $sense2 ) {
		$this->assertTrue( $sense1->equals( $sense2 ) );
	}

	public function provideUnequalSenses() {
		$sense = NewSense::havingId( 'S1' )->withGloss( 'en', 'minimal' );

		return [
			'different glosses' => [
				$sense->build(),
				NewSense::havingId( 'S1' )->withGloss( 'en', 'different' )->build()
			],
			'+1 gloss' => [
				$sense->build(),
				$sense->withGloss( 'fr', 'two' )->build()
			],
			'+1 statement' => [
				$sense->build(),
				$sense->withStatement( $this->newStatement() )->build()
			],
		];
	}

	/**
	 * @dataProvider provideUnequalSenses
	 */
	public function testGivenUnequalSenses_areNotEqual( Sense $sense1, Sense $sense2 ) {
		$this->assertFalse( $sense1->equals( $sense2 ) );
	}

	private function newStatement() {
		return new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) ) );
	}

	/**
	 * @dataProvider clearableProvider
	 */
	public function testClear( Sense $sense ) {
		$clone = $sense->copy();

		$sense->clear();

		$this->assertTrue( $sense->isEmpty(), 'sense should be empty after clear' );
		$this->assertEquals( $clone->getId(), $sense->getId(), 'ids must be equal' );
	}

	public function clearableProvider() {
		return [
			'empty' => [ NewSense::havingId( 'S1' )->build() ],
			'with gloss' => [
				NewSense::havingId( 'S2' )
					->withGloss( 'en', 'foo' )
					->build(),
			],
			'with statement' => [
				NewSense::havingId( 'S4' )
					->withStatement( new PropertyNoValueSnak( 42 ) )
					->build(),
			],
		];
	}

}
