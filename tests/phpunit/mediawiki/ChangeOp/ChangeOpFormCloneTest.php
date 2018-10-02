<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\ChangeOp\ChangeOpFormClone;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DummyObjects\BlankForm;
use Wikibase\Lexeme\DummyObjects\DummyFormId;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Tests\NewStatement;

/**
 * @coversDefaultClass \Wikibase\Lexeme\ChangeOp\ChangeOpFormClone
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFormCloneTest extends TestCase {

	use PHPUnit4And6Compat;

	/**
	 * @var GuidGenerator|MockObject
	 */
	private $guidGenerator;

	public function setUp() {
		$this->guidGenerator = $this->createMock( GuidGenerator::class );
	}

	/**
	 * @covers ::validate
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testValidateNonForm_yieldsAssertionProblem() {
		$changeOp = $this->newChangeOpFormClone( NewForm::any()->build() );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	/**
	 * @covers ::validate
	 */
	public function testValidateAnyForm_yieldsSuccess() {
		$changeOp = $this->newChangeOpFormClone( NewForm::any()->build() );
		$result = $changeOp->validate( new BlankForm() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	/**
	 * @covers ::apply
	 */
	public function testApply() {
		$sourceForm = NewForm::havingId( 'F71' )
			->andLexeme( new LexemeId( 'L42' ) )
			->andGrammaticalFeature( 'Q11' )
			->andGrammaticalFeature( 'Q7' )
			->andRepresentation( 'en-us', 'colorful' )
			->andStatement(
				NewStatement::forProperty( 'P4711' )
					->withSomeGuid()->withValue( new LexemeId( 'L123' ) )
			)
			->build();

		$this->guidGenerator
			->expects( $this->once() )
			->method( 'newGuid' )
			->with( 'L34-F1' )
			->willReturn( 'L34-F1$00000000-0000-0000-0000-000000000000' );

		$changeOp = $this->newChangeOpFormClone( $sourceForm );

		$targetForm = new BlankForm();
		$targetForm->setLexeme( NewLexeme::havingId( 'L34' )->build() );
		$changeOp->apply( $targetForm );

		$this->assertInstanceOf( DummyFormId::class, $targetForm->getId() );
		$this->assertEquals(
			[ new ItemId( 'Q11' ), new ItemId( 'Q7' ) ],
			$targetForm->getGrammaticalFeatures()
		);
		$this->assertSame(
			[ 'en-us' => 'colorful' ],
			$targetForm->getRepresentations()->toTextArray()
		);

		$statements = $targetForm->getStatements();
		$this->assertCount( 1, $statements );
		$statement = $statements->toArray()[0];
		$this->assertSame( 'L34-F1$00000000-0000-0000-0000-000000000000', $statement->getGuid() );
		$snak = $statement->getMainSnak();
		$this->assertSame( 'P4711', $snak->getPropertyId()->serialize() );
		$this->assertSame( 'value', $snak->getType() );
		$this->assertSame( 'L123', $snak->getDataValue()->getEntityId()->serialize() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::apply
	 */
	public function testApply_doesNotModifySourceForm() {
		$originalSourceForm = NewForm::havingId( 'F71' )
			->andLexeme( new LexemeId( 'L42' ) )
			->andRepresentation( 'en-us', 'colorful' )
			->andStatement(
				NewStatement::forProperty( 'P4711' )
					->withSomeGuid()->withValue( new LexemeId( 'L123' ) )
			)
			->build();
		$sourceForm = $originalSourceForm->copy();
		$changeOp = $this->newChangeOpFormClone( $sourceForm );

		$targetForm = new BlankForm();
		$targetForm->setLexeme( NewLexeme::havingId( 'L34' )->build() );
		$changeOp->apply( $targetForm );

		$this->assertEquals( $originalSourceForm, $sourceForm );
	}

	/**
	 * @covers ::getActions
	 */
	public function testGetActions() {
		$sourceForm = $this->getMockBuilder( Form::class )
			->disableOriginalConstructor()
			->getMock();
		$changeOp = $this->newChangeOpFormClone( $sourceForm );

		$this->assertSame( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

	private function newChangeOpFormClone( Form $sourceForm ) : ChangeOpFormClone {
		return new ChangeOpFormClone( $sourceForm, $this->guidGenerator );
	}

}
