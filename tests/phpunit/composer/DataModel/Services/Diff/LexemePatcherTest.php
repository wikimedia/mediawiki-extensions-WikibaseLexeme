<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiff;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemePatcher;

/**
 * @covers Wikibase\Lexeme\DataModel\Services\Diff\LexemePatcher
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemePatcherTest extends PHPUnit_Framework_TestCase {

	public function testGivenEmptyDiff_lexemeIsReturnedAsIs() {
		$lexeme = new Lexeme();

		$patched = $lexeme->copy();
		$patcher = new LexemePatcher();
		$patcher->patchEntity( $patched, new LexemeDiff() );

		$this->assertInstanceOf( Lexeme::class, $patched );
		$this->assertTrue( $lexeme->equals( $patched ) );
	}

	public function testCanPatchEntityType() {
		$patcher = new LexemePatcher();
		$this->assertTrue( $patcher->canPatchEntityType( 'lexeme' ) );
		$this->assertFalse( $patcher->canPatchEntityType( 'property' ) );
		$this->assertFalse( $patcher->canPatchEntityType( '' ) );
		$this->assertFalse( $patcher->canPatchEntityType( null ) );
	}

	public function testGivenNonLexeme_exceptionIsThrown() {
		$patcher = new LexemePatcher();

		$this->setExpectedException( InvalidArgumentException::class );
		$patcher->patchEntity( new Item(), new LexemeDiff() );
	}

	public function testStatementsArePatched() {
		$removedStatement = new Statement( new PropertyNoValueSnak( 1 ), null, null, 's1' );
		$addedStatement = new Statement( new PropertyNoValueSnak( 2 ), null, null, 's2' );

		$lexeme = new Lexeme();
		$lexeme->getStatements()->addStatement( $removedStatement );

		$patch = new LexemeDiff( [
			'claim' => new Diff( [
				's1' => new DiffOpRemove( $removedStatement ),
				's2' => new DiffOpAdd( $addedStatement ),
			] ),
		] );

		$expected = new Lexeme();
		$expected->getStatements()->addStatement( $addedStatement );

		$patcher = new LexemePatcher();
		$patcher->patchEntity( $lexeme, $patch );
		$this->assertEquals( $expected->getStatements(), $lexeme->getStatements() );
		$this->assertTrue( $expected->equals( $lexeme ) );
	}

}
