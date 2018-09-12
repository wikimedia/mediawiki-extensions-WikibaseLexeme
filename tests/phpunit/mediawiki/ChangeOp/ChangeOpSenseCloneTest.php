<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\Lexeme\ChangeOp\ChangeOpSenseClone;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DummyObjects\BlankSense;
use Wikibase\Lexeme\DummyObjects\DummySenseId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Tests\NewStatement;

/**
 * @coversDefaultClass \Wikibase\Lexeme\ChangeOp\ChangeOpSenseClone
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpSenseCloneTest extends TestCase {

	/**
	 * @covers ::validate
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testValidateNonSense_yieldsAssertionProblem() {
		$changeOp = new ChangeOpSenseClone( NewSense::havingId( 'S1' )->build() );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	/**
	 * @covers ::validate
	 */
	public function testValidateAnySense_yieldsSuccess() {
		$changeOp = new ChangeOpSenseClone( NewSense::havingId( 'S1' )->build() );
		$result = $changeOp->validate( new BlankSense() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	/**
	 * @covers ::apply
	 */
	public function testApply() {
		$sourceSense = NewSense::havingId( 'S71' )
			->andLexeme( new LexemeId( 'L42' ) )
			->withGloss( 'en-us', 'colorful' )
			->withStatement(
				NewStatement::forProperty( 'P4711' )
					->withSomeGuid()->withValue( new LexemeId( 'L123' ) )
			)
			->build();
		$changeOp = new ChangeOpSenseClone( $sourceSense );

		$targetSense = new BlankSense();
		$targetSense->setLexeme( NewLexeme::havingId( 'L34' )->build() );
		$changeOp->apply( $targetSense );

		$this->assertInstanceOf( DummySenseId::class, $targetSense->getId() );
		$this->assertSame(
			[ 'en-us' => 'colorful' ],
			$targetSense->getGlosses()->toTextArray()
		);

		$statements = $targetSense->getStatements();
		$this->assertCount( 1, $statements );
		$statement = $statements->toArray()[0];
		$this->assertNull( $statement->getGuid() );
		$snak = $statement->getMainSnak();
		$this->assertSame( 'P4711', $snak->getPropertyId()->serialize() );
		$this->assertSame( 'value', $snak->getType() );
		$this->assertSame( 'L123', $snak->getDataValue()->getEntityId()->serialize() );
	}

	/**
	 * @covers ::getActions
	 */
	public function testGetActions() {
		$sourceSense = $this->getMockBuilder( Sense::class )
			->disableOriginalConstructor()
			->getMock();
		$changeOp = new ChangeOpSenseClone( $sourceSense );

		$this->assertSame( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

}
