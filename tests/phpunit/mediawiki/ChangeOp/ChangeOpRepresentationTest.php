<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentation;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Summary;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentation
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpRepresentationTest extends TestCase {

	public function testAction_isEdit() {
		$changeOp = new ChangeOpRepresentation( new Term( 'en', 'goat' ) );
		$this->assertSame( [ 'edit' ], $changeOp->getActions() );
	}

	public function testValidateNonForm_yieldsAssertionProblem() {
		$changeOp = new ChangeOpRepresentation( new Term( 'en', 'goat' ) );
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	public function testValidateAnyForm_yieldsSuccess() {
		$changeOp = new ChangeOpRepresentation( new Term( 'en', 'goat' ) );
		$result = $changeOp->validate( NewForm::any()->build() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	public function testApplyNonForm_yieldsAssertionProblem() {
		$changeOp = new ChangeOpRepresentation( new Term( 'en', 'goat' ) );
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
		$changeOp->apply( NewLexeme::create()->build() );
	}

	public function testApply_addsRepresentationInNewLanguage() {
		$form = NewForm::havingRepresentation( 'de', 'Ziege' )
			->andId( new FormId( 'L1-F3' ) )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpRepresentation( new Term( 'en', 'goat' ) );
		$changeOp->apply( $form, $summary );

		$this->assertCount( 2, $form->getRepresentations() );
		$this->assertSame( 'add-form-representations', $summary->getMessageKey() );
		$this->assertSame( 'en', $summary->getLanguageCode() );
		$this->assertSame( [ 'L1-F3' ], $summary->getCommentArgs() );
		$this->assertSame( [ 'en' => 'goat' ], $summary->getAutoSummaryArgs() );
	}

	public function testApply_replacesRepresentationInPreexistingLanguage() {
		$form = NewForm::havingRepresentation( 'de', 'Ziege' )
			->andId( new FormId( 'L1-F3' ) )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpRepresentation( new Term( 'de', 'Zicke' ) );
		$changeOp->apply( $form, $summary );

		$this->assertCount( 1, $form->getRepresentations() );
		$this->assertSame( 'set-form-representations', $summary->getMessageKey() );
		$this->assertSame( 'de', $summary->getLanguageCode() );
		$this->assertSame( [ 'L1-F3' ], $summary->getCommentArgs() );
		$this->assertSame( [ 'de' => 'Zicke' ], $summary->getAutoSummaryArgs() );
	}

	public function testApply_noSummaryForSameTermInPreexistingLanguage() {
		$form = NewForm::havingRepresentation( 'de', 'Ziege' )
			->andId( new FormId( 'L1-F3' ) )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpRepresentation( new Term( 'de', 'Ziege' ) );
		$changeOp->apply( $form, $summary );

		$this->assertCount( 1, $form->getRepresentations() );
		$this->assertNull( $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [], $summary->getCommentArgs() );
		$this->assertSame( [], $summary->getAutoSummaryArgs() );
	}

}
