<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseClone;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\DummyObjects\DummySenseId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @coversDefaultClass \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseClone
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpSenseCloneTest extends TestCase {

	/**
	 * @covers ::validate
	 */
	public function testValidateNonSense_yieldsAssertionProblem() {
		$changeOp = new ChangeOpSenseClone( NewSense::havingId( 'S1' )->build() );
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
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
		$lexeme = NewLexeme::havingId( 'L34' )->build();
		$lexeme->addOrUpdateSense( $targetSense );

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
	 * @covers ::__construct
	 * @covers ::apply
	 */
	public function testApply_doesNotModifySourceSense() {
		$originalSourceSense = NewSense::havingId( 'S71' )
			->andLexeme( new LexemeId( 'L42' ) )
			->withGloss( 'en-us', 'colorful' )
			->withStatement(
				NewStatement::forProperty( 'P4711' )
					->withSomeGuid()->withValue( new LexemeId( 'L123' ) )
			)
			->build();
		$sourceSense = $originalSourceSense->copy();
		$changeOp = new ChangeOpSenseClone( $sourceSense );

		$targetSense = new BlankSense();
		$lexeme = NewLexeme::havingId( 'L34' )->build();
		$lexeme->addOrUpdateSense( $targetSense );

		$changeOp->apply( $targetSense );

		$this->assertEquals( $originalSourceSense, $sourceSense );
	}

	/**
	 * @covers ::getActions
	 */
	public function testGetActions() {
		$sourceSense = $this->createMock( Sense::class );
		$changeOp = new ChangeOpSenseClone( $sourceSense );

		$this->assertSame( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

}
