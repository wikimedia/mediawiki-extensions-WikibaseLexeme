<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\FormSet;
use Wikibase\Lexeme\Domain\Model\LexemePatchAccess;
use Wikibase\Lexeme\Domain\Model\SenseSet;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;

/**
 * @covers \Wikibase\Lexeme\Domain\Model\LexemePatchAccess
 *
 * @license GPL-2.0-or-later
 */
class LexemePatchAccessTest extends TestCase {

	public function testCanAddAForm() {
		$forms = new FormSet();
		$lexemePatchAccess = new LexemePatchAccess( 1, $forms, 1, new SenseSet() );
		$form = NewForm::any()->build();

		$lexemePatchAccess->addForm( $form );

		$this->assertEquals( new FormSet( [ $form ] ), $lexemePatchAccess->getForms() );
	}

	public function testCanNotAddAFormIfPatchAccessIsClosed() {
		$forms = new FormSet();
		$lexemePatchAccess = new LexemePatchAccess( 1, $forms, 1, new SenseSet() );
		$form = NewForm::any()->build();
		$lexemePatchAccess->close();

		$this->expectException( \Exception::class );
		$lexemePatchAccess->addForm( $form );
	}

	public function testDoesNotModifyTheOriginalFormSet() {
		$initialFormList = [];
		$forms = new FormSet( $initialFormList );
		$lexemePatchAccess = new LexemePatchAccess( 1, $forms, 1, new SenseSet() );
		$form = NewForm::any()->build();

		$lexemePatchAccess->addForm( $form );

		$this->assertEquals( $initialFormList, $forms->toArray() );
	}

	public function testCanNotCreateWithNextFromIdWhichIsNotAPositiveInteger() {
		$this->expectException( \Exception::class );
		new LexemePatchAccess( 0, new FormSet(), 1, new SenseSet() );
	}

	public function testIncreaseNextFormIdTo_GivenLexemWithGreaterId_Increases() {
		$lexemePatchAccess = new LexemePatchAccess( 1, new FormSet(), 1, new SenseSet() );

		$lexemePatchAccess->increaseNextFormIdTo( 2 );

		$this->assertEquals( 2, $lexemePatchAccess->getNextFormId() );
	}

	public function testIncreaseNextFormIdTo_GivenLexemeSmallerInitialId_ThrowsAnException() {
		$lexemePatchAccess = new LexemePatchAccess( 2, new FormSet(), 1, new SenseSet() );

		$this->expectException( \Exception::class );
		$lexemePatchAccess->increaseNextFormIdTo( 1 );
	}

	public function testIncreaseNextFormIdTo_GivenNonInteger_ThrowsAnException() {
		$lexemePatchAccess = new LexemePatchAccess( 1, new FormSet(), 1, new SenseSet() );

		$this->expectException( \Exception::class );
		$lexemePatchAccess->increaseNextFormIdTo( 2.0 );
	}

	public function testCanAddASense() {
		$lexemePatchAccess = new LexemePatchAccess( 1, new FormSet(), 1, new SenseSet() );
		$sense = NewSense::havingId( 'S1' )->build();

		$lexemePatchAccess->addSense( $sense );

		$this->assertEquals( new SenseSet( [ $sense ] ), $lexemePatchAccess->getSenses() );
	}

	public function testCanNotAddASenseIfPatchAccessIsClosed() {
		$lexemePatchAccess = new LexemePatchAccess( 1, new FormSet(), 1, new SenseSet() );
		$sense = NewSense::havingId( 'S1' )->build();
		$lexemePatchAccess->close();

		$this->expectException( \Exception::class );
		$lexemePatchAccess->addSense( $sense );
	}

	public function testDoesNotModifyTheOriginalSenseSet() {
		$initialSenseList = [];
		$senses = new SenseSet( $initialSenseList );
		$lexemePatchAccess = new LexemePatchAccess( 1, new FormSet(), 1, $senses );
		$sense = NewSense::havingId( 'S1' )->build();

		$lexemePatchAccess->addSense( $sense );

		$this->assertSame( $initialSenseList, $senses->toArray() );
	}

	public function testCanNotCreateWithNextSenseIdWhichIsNotAPositiveInteger() {
		$this->expectException( \Exception::class );
		new LexemePatchAccess( 1, new FormSet(), 0, new SenseSet() );
	}

	public function testIncreaseNextSenseIdTo_GivenLexemeWithGreaterId_Increases() {
		$lexemePatchAccess = new LexemePatchAccess( 1, new FormSet(), 1, new SenseSet() );

		$lexemePatchAccess->increaseNextSenseIdTo( 2 );

		$this->assertSame( 2, $lexemePatchAccess->getNextSenseId() );
	}

	public function testIncreaseNextSenseIdTo_GivenLexemeSmallerInitialId_ThrowsAnException() {
		$lexemePatchAccess = new LexemePatchAccess( 1, new FormSet(), 2, new SenseSet() );

		$this->expectException( \Exception::class );
		$lexemePatchAccess->increaseNextSenseIdTo( 1 );
	}

	public function testIncreaseNextSenseIdTo_GivenNonInteger_ThrowsAnException() {
		$lexemePatchAccess = new LexemePatchAccess( 1, new FormSet(), 1, new SenseSet() );

		$this->expectException( \Exception::class );
		$lexemePatchAccess->increaseNextSenseIdTo( 2.0 );
	}

}
