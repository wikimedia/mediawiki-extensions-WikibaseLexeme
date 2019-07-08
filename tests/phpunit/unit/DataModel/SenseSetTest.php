<?php

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Domain\Model\SenseSet;

/**
 * @covers \Wikibase\Lexeme\Domain\Model\SenseSet
 *
 * @license GPL-2.0-or-later
 */
class SenseSetTest extends MediaWikiUnitTestCase {

	/**
	 * @expectedException \Exception
	 */
	public function testCanNotCreateWithSomethingThatIsNotASense() {
		new SenseSet( [ 1 ] );
	}

	public function testToArray() {
		$sense = NewSense::havingId( 'S1' )->build();
		$senseSet = new SenseSet( [ $sense ] );

		$this->assertSame( [ $sense ], $senseSet->toArray() );
	}

	/**
	 * @expectedException \Exception
	 */
	public function testCanNotCreateWithTwoSensesHavingTheSameId() {
		new SenseSet(
			[
				NewSense::havingId( 'S1' )->build(),
				NewSense::havingId( 'S1' )->build(),
			]
		);
	}

	public function testCount() {
		$this->assertSame( 0, ( new SenseSet() )->count() );
		$sense = NewSense::havingId( 'S1' )->build();
		$this->assertSame( 1, ( new SenseSet( [ $sense ] ) )->count() );
	}

	public function testIsEmpty() {
		$this->assertTrue( ( new SenseSet() )->isEmpty() );
		$sense = NewSense::havingId( 'S1' )->build();
		$this->assertFalse( ( new SenseSet( [ $sense ] ) )->isEmpty() );
	}

	public function testMaxSenseIdNumber_EmptySet_ReturnsZero() {
		$this->assertSame( 0, ( new SenseSet() )->maxSenseIdNumber() );
	}

	public function testMaxSenseIdNumber_SetWithOneSense_ReturnsThatSenseIdNumber() {
		$senseSet = new SenseSet( [ NewSense::havingId( 'S5' )->build() ] );

		$this->assertSame( 5, $senseSet->maxSenseIdNumber() );
	}

	public function testMaxSenseIdNumber_SetWithManySenses_ReturnsMaximumSenseIdNumber() {
		$senseSet = new SenseSet(
			[
				NewSense::havingId( 'S1' )->build(),
				NewSense::havingId( 'S3' )->build(),
				NewSense::havingId( 'S2' )->build(),
			]
		);

		$this->assertSame( 3, $senseSet->maxSenseIdNumber() );
	}

	public function testAddSense_EmptySet_SenseIsAdded() {
		$senseSet = new SenseSet();
		$sense = NewSense::havingId( 'S1' )->build();

		$senseSet->add( $sense );

		$this->assertSame( [ $sense ], $senseSet->toArray() );
	}

	/**
	 * @expectedException \Exception
	 */
	public function testAddSense_AddSenseWithIdThatAlreadyPresentInTheSet_ThrowsAnException() {
		$senseSet = new SenseSet( [ NewSense::havingId( 'S1' )->build() ] );

		$senseSet->add( NewSense::havingId( 'S1' )->build() );
	}

	public function testRemove_CanRemoveASense() {
		$senseSet = new SenseSet( [ NewSense::havingId( 'S1' )->build() ] );

		$senseSet->remove( new SenseId( 'L1-S1' ) );

		$this->assertEmpty( $senseSet->toArray() );
	}

	public function testPut_updatedSenseReference() {
		$sense = NewSense::havingId( 'S1' )->build();
		$senseSet = new SenseSet( [ $sense ] );

		$newSense = NewSense::havingId( 'S1' )->build();
		$this->assertNotSame( $sense, $newSense ); // sanity check
		$senseSet->put( $newSense );

		$this->assertSame( [ $newSense ], $senseSet->toArray() );
		$this->assertNotSame( [ $sense ], $senseSet->toArray() ); // sanity check
	}

	public function testIndependentlyOnSenseAdditionOrder_TwoSetsAreEqualIfTheyHaveTheSameSenses() {
		$sense1 = NewSense::havingId( 'S1' )->build();
		$sense2 = NewSense::havingId( 'S2' )->build();

		$senseSet1 = new SenseSet( [ $sense1, $sense2 ] );
		$senseSet2 = new SenseSet( [ $sense2, $sense1 ] );

		$this->assertEquals( $senseSet1, $senseSet2 );
	}

	/**
	 * Senses can only be accessed through SenseSet::toArray(), which enforces right order,
	 * or one-by-one through id, where order is irrelevant.
	 */
	public function testToArray_ReturnedSensesAreSortedByTheirId() {
		$sense1 = NewSense::havingId( 'S2' )->build();
		$sense2 = NewSense::havingId( 'S12' )->build();

		$senseSet = new SenseSet( [ $sense2, $sense1 ] );

		$this->assertSame( [ $sense1, $sense2 ], $senseSet->toArray() );
	}

	public function testCopyClonesSenses() {
		$sense1 = NewSense::havingId( 'S1' )->build();
		$sense2 = NewSense::havingId( 'S2' )->build();
		$senseSet = new SenseSet( [ $sense2, $sense1 ] );

		$senseSetCopy = $senseSet->copy();

		$this->assertNotSame(
			$senseSet,
			$senseSetCopy
		);
		$this->assertNotSame(
			$senseSet->getById( $sense1->getId() ),
			$senseSetCopy->getById( $sense1->getId() )
		);
	}

	/**
	 * @dataProvider equalsProvider
	 */
	public function testEquals( SenseSet $set1, $set2, $isEqual ) {
		$this->assertSame( $isEqual, $set1->equals( $set2 ) );
	}

	public function equalsProvider() {
		yield 'empty sets' => [
			new SenseSet(),
			new SenseSet(),
			true
		];

		yield 'not a SenseSet - not equal' => [
			new SenseSet(),
			[],
			false
		];

		$sense = NewSense::havingId( new SenseId( 'L1-S1' ) )
			->withGloss( 'en', 'potato' )
			->build();
		yield 'same Sense' => [
			new SenseSet( [ $sense ] ),
			new SenseSet( [ $sense->copy() ] ),
			true
		];

		$sense2 = NewSense::havingId( new SenseId( 'L12-S2' ) )
			->withGloss( 'de', 'Kartoffel' )
			->build();
		yield 'different order of Senses' => [
			new SenseSet( [ $sense, $sense2 ] ),
			new SenseSet( [ $sense2, $sense ] ),
			true
		];

		$blankSense = new BlankSense();
		$blankSense->setGlosses( $sense->getGlosses() );
		yield 'Sense and equivalent BlankSense' => [
			new SenseSet( [ $sense ] ),
			new SenseSet( [ $blankSense->getRealSense( $sense->getId() ) ] ),
			true
		];

		$sense3 = NewSense::havingId( new SenseId( 'L12-S3' ) )
			->withGloss( 'ru', 'карто́фель' )
			->build();
		yield 'one replaced Sense but same length' => [
			new SenseSet( [ $sense, $sense2 ] ),
			new SenseSet( [ $sense, $sense3 ] ),
			false
		];
	}

}
