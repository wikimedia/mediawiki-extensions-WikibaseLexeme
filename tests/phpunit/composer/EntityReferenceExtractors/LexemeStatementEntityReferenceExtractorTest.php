<?php

namespace Wikibase\Lexeme\Tests\EntityReferenceExtractors;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\EntityReferenceExtractors\FormsStatementEntityReferenceExtractor;
use Wikibase\Lexeme\EntityReferenceExtractors\LexemeStatementEntityReferenceExtractor;
use Wikibase\Lexeme\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;

/**
 * @covers \Wikibase\Lexeme\EntityReferenceExtractors\LexemeStatementEntityReferenceExtractor
 *
 * @license GPL-2.0-or-later
 */
class LexemeStatementEntityReferenceExtractorTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testSubExtractorsAreAllCalledOnceAndResultCombined() {
		$lexeme = NewLexeme::havingId( new LexemeId( 'L98' ) )->build();

		$statementRefExtractor = $this->getMock(
			StatementEntityReferenceExtractor::class, [], [], '', false
		);
		$statementRefExtractor->expects( $this->once() )
			->method( 'extractEntityIds' )
			->with( $lexeme )
			->willReturn( [ new LexemeId( 'L1' ) ] );

		$formStatementRefExtractor = $this->getMock(
			FormsStatementEntityReferenceExtractor::class, [], [], '', false
		);
		$formStatementRefExtractor->expects( $this->once() )
			->method( 'extractEntityIds' )
			->with( $lexeme )
			->willReturn( [ new LexemeId( 'L2' ) ] );

		$senseStatementRefExtractor = $this->getMock(
			SensesStatementEntityReferenceExtractor::class, [], [], '', false
		);
		$senseStatementRefExtractor->expects( $this->once() )
			->method( 'extractEntityIds' )
			->with( $lexeme )
			->willReturn( [ new LexemeId( 'L3' ) ] );

		/**
		 * @var StatementEntityReferenceExtractor $statementRefExtractor
		 * @var FormsStatementEntityReferenceExtractor $formStatementRefExtractor
		 * @var SensesStatementEntityReferenceExtractor $senseStatementRefExtractor
		 */
		$extractor = new LexemeStatementEntityReferenceExtractor(
			$statementRefExtractor,
			$formStatementRefExtractor,
			$senseStatementRefExtractor
		);

		$result = $extractor->extractEntityIds( $lexeme );

		$this->assertEquals(
			[
				new LexemeId( 'L1' ),
				new LexemeId( 'L2' ),
				new LexemeId( 'L3' ),
			],
			$result
		);
	}

}
