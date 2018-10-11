<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\ChangeOp\ChangeOpGrammaticalFeatures;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\ChangeOpGrammaticalFeatures
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpGrammaticalFeaturesTest extends TestCase {

	public function testAction_isEdit() {
		$changeOp = new ChangeOpGrammaticalFeatures( [] );
		$this->assertSame( [ 'edit' ], $changeOp->getActions() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testValidateNonForm_yieldsAssertionProblem() {
		$changeOp = new ChangeOpGrammaticalFeatures( [] );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	public function testValidateAnyForm_yieldsSuccess() {
		$changeOp = new ChangeOpGrammaticalFeatures( [] );
		$result = $changeOp->validate( NewForm::any()->build() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testApplyNonForm_yieldsAssertionProblem() {
		$changeOp = new ChangeOpGrammaticalFeatures( [] );
		$changeOp->apply( NewLexeme::create()->build() );
	}

	public function testApply_removesPreexistingFeature() {
		$existingFeature = new ItemId( 'Q123' );

		$form = NewForm::havingGrammaticalFeature( $existingFeature )
			->andId( new FormId( 'L1-F3' ) )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpGrammaticalFeatures( [] );
		$changeOp->apply( $form, $summary );

		$this->assertCount( 0, $form->getGrammaticalFeatures() );
		$this->assertSame( 'remove-form-grammatical-features', $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [ 'L1-F3' ], $summary->getCommentArgs() );
		$this->assertSame( [ $existingFeature ], $summary->getAutoSummaryArgs() );
	}

	public function testApply_keepsExistingFeatures() {
		$existingFeature = new ItemId( 'Q123' );

		$form = NewForm::havingGrammaticalFeature( $existingFeature )
			->andId( new FormId( 'L1-F3' ) )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpGrammaticalFeatures( [ $existingFeature ] );
		$changeOp->apply( $form, $summary );

		$this->assertCount( 1, $form->getGrammaticalFeatures() );
		$this->assertNull( $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [], $summary->getCommentArgs() );
		$this->assertSame( [], $summary->getAutoSummaryArgs() );
	}

	public function testApply_addsNewFeatures() {
		$existingFeature = new ItemId( 'Q123' );
		$newFeature = new ItemId( 'Q777' );

		$form = NewForm::havingGrammaticalFeature( $existingFeature )
			->andId( new FormId( 'L1-F3' ) )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpGrammaticalFeatures( [ $existingFeature, $newFeature ] );
		$changeOp->apply( $form, $summary );

		$this->assertCount( 2, $form->getGrammaticalFeatures() );
		$this->assertSame( 'add-form-grammatical-features', $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [ 'L1-F3' ], $summary->getCommentArgs() );
		$this->assertSame( [ $newFeature ], $summary->getAutoSummaryArgs() );
	}

	public function testApply_addsNewAndRemovesExistingFeatures() {
		$existingFeature = new ItemId( 'Q123' );
		$newFeature = new ItemId( 'Q777' );

		$form = NewForm::havingGrammaticalFeature( $existingFeature )
			->andId( new FormId( 'L1-F3' ) )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpGrammaticalFeatures( [ $newFeature ] );
		$changeOp->apply( $form, $summary );

		$this->assertCount( 1, $form->getGrammaticalFeatures() );
		$this->assertSame( 'update-form-grammatical-features', $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [ 'L1-F3' ], $summary->getCommentArgs() );
		$this->assertSame( [], $summary->getAutoSummaryArgs() );
	}

}
