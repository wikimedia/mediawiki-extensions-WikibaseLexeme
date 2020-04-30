<?php

namespace Wikibase\Lexeme\Tests\Unit\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use InvalidArgumentException;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Diff\LexemeDiff;
use Wikibase\Lexeme\Domain\Diff\LexemePatcher;
use Wikibase\Lexeme\Domain\Model\Lexeme;

/**
 * @covers \Wikibase\Lexeme\Domain\Diff\LexemePatcher
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemePatcherTest extends MediaWikiUnitTestCase {

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

		$this->expectException( InvalidArgumentException::class );
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

	public function testLemmasArePatched() {
		$removedLemma = new Term( 'en', 'Alan Turing' );
		$addedLemma = new Term( 'fa', 'آلن تورینگ' );

		$lexeme = new Lexeme();
		$lexeme->setLemmas( new TermList( [ $removedLemma ] ) );

		$patch = new LexemeDiff( [
			'lemmas' => new Diff( [
				'en' => new DiffOpRemove( 'Alan Turing' ),
				'fa' => new DiffOpAdd( 'آلن تورینگ' ),
			] ),
		] );

		$expected = new Lexeme();
		$expected->setLemmas( new TermList( [ $addedLemma ] ) );

		$patcher = new LexemePatcher();
		$patcher->patchEntity( $lexeme, $patch );
		$this->assertEquals( $expected->getLemmas(), $lexeme->getLemmas() );
		$this->assertTrue( $expected->equals( $lexeme ) );
	}

	public function testLexicalCategoryIsPatched() {
		$removedLexicalCategory = new ItemId( 'Q11' );
		$addedLexicalCategory = new ItemId( 'Q22' );

		$lexeme = new Lexeme();
		$lexeme->setLexicalCategory( $removedLexicalCategory );

		$patch = new LexemeDiff( [
			'lexicalCategory' => new Diff( [
				'id' => new DiffOpChange( $removedLexicalCategory, $addedLexicalCategory ),
			] ),
		] );

		$expected = new Lexeme();
		$expected->setLexicalCategory( $addedLexicalCategory );

		$patcher = new LexemePatcher();
		$patcher->patchEntity( $lexeme, $patch );
		$this->assertEquals( $expected->getLexicalCategory(), $lexeme->getLexicalCategory() );
		$this->assertTrue( $expected->equals( $lexeme ) );
	}

	public function testLanguageIsPatched() {
		$removedLanguage = new ItemId( 'Q1' );
		$addedLanguage = new ItemId( 'Q2' );

		$lexeme = new Lexeme();
		$lexeme->setLanguage( $removedLanguage );

		$patch = new LexemeDiff( [
			'language' => new Diff( [
				'id' => new DiffOpChange( $removedLanguage, $addedLanguage ),
			] ),
		] );

		$expected = new Lexeme();
		$expected->setLanguage( $addedLanguage );

		$patcher = new LexemePatcher();
		$patcher->patchEntity( $lexeme, $patch );
		$this->assertEquals( $expected->getLanguage(), $lexeme->getLanguage() );
		$this->assertTrue( $expected->equals( $lexeme ) );
	}

	public function testElementsNotInDiffAreNotRemoved() {
		$lexeme = new Lexeme();
		$lexeme->setLexicalCategory( new ItemId( 'Q1' ) );
		$lexeme->setLanguage( new ItemId( 'Q2' ) );

		$patcher = new LexemePatcher();
		$patcher->patchEntity( $lexeme, new LexemeDiff() );

		$this->assertNotNull( $lexeme->getLexicalCategory() );
		$this->assertNotNull( $lexeme->getLanguage() );
	}

}
