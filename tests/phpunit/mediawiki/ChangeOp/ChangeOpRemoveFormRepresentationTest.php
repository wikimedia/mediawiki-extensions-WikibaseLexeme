<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveFormRepresentation;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Summary;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveFormRepresentation
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpRemoveFormRepresentationTest extends TestCase {

	public function testAction_isEdit() {
		$changeOp = new ChangeOpRemoveFormRepresentation( 'en' );
		$this->assertSame( [ 'edit' ], $changeOp->getActions() );
	}

	public function testValidateNonForm_yieldsAssertionProblem() {
		$changeOp = new ChangeOpRemoveFormRepresentation( 'en' );
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	public function testValidateAnyForm_yieldsSuccess() {
		$changeOp = new ChangeOpRemoveFormRepresentation( 'en' );
		$result = $changeOp->validate( NewForm::any()->build() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	public function testApplyNonForm_yieldsAssertionProblem() {
		$changeOp = new ChangeOpRemoveFormRepresentation( 'en' );
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
		$changeOp->apply( NewLexeme::create()->build() );
	}

	public function testApply_removesRepresentationInPreexistingLanguage() {
		$form = NewForm::havingRepresentation( 'en', 'goat' )
			->andId( new FormId( 'L1-F3' ) )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpRemoveFormRepresentation( 'en' );
		$changeOp->apply( $form, $summary );

		$this->assertCount( 0, $form->getRepresentations() );
		$this->assertSame( 'remove-form-representations', $summary->getMessageKey() );
		$this->assertSame( 'en', $summary->getLanguageCode() );
		$this->assertSame( [ 'L1-F3' ], $summary->getCommentArgs() );
		$this->assertSame( [ 'en' => 'goat' ], $summary->getAutoSummaryArgs() );
	}

	public function testApply_noOpForNonPreexistingLanguage() {
		$form = NewForm::havingRepresentation( 'en', 'goat' )
			->andId( new FormId( 'L1-F3' ) )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpRemoveFormRepresentation( 'de' );
		$changeOp->apply( $form, $summary );

		$this->assertCount( 1, $form->getRepresentations() );
		$this->assertNull( $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [], $summary->getCommentArgs() );
		$this->assertSame( [], $summary->getAutoSummaryArgs() );
	}

}
