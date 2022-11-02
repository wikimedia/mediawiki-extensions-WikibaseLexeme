<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormClone;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\DummyObjects\DummyFormId;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @coversDefaultClass \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormClone
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFormCloneTest extends TestCase {

	/**
	 * @var GuidGenerator|MockObject
	 */
	private $guidGenerator;

	protected function setUp(): void {
		parent::setUp();
		$this->guidGenerator = $this->createMock( GuidGenerator::class );
	}

	/**
	 * @covers ::validate
	 */
	public function testValidateNonForm_yieldsAssertionProblem() {
		$changeOp = $this->newChangeOpFormClone( NewForm::any()->build() );
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
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
		$lexeme = NewLexeme::havingId( 'L34' )->build();
		$lexeme->addOrUpdateForm( $targetForm );

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
		$lexeme = NewLexeme::havingId( 'L34' )->build();
		$lexeme->addOrUpdateForm( $targetForm );
		$changeOp->apply( $targetForm );

		$this->assertEquals( $originalSourceForm, $sourceForm );
	}

	/**
	 * @covers ::getActions
	 */
	public function testGetActions() {
		$sourceForm = $this->createMock( Form::class );
		$changeOp = $this->newChangeOpFormClone( $sourceForm );

		$this->assertSame( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

	private function newChangeOpFormClone( Form $sourceForm ): ChangeOpFormClone {
		return new ChangeOpFormClone( $sourceForm, $this->guidGenerator );
	}

}
