<?php

namespace Wikibase\Lexeme\Tests\EntityReferenceExtractors;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\FormsStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LexemeStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;

/**
 * @covers \Wikibase\Lexeme\Domain\EntityReferenceExtractors\LexemeStatementEntityReferenceExtractor
 *
 * @license GPL-2.0-or-later
 */
class LexemeStatementEntityReferenceExtractorTest extends TestCase {

	public function testSubExtractorsAreAllCalledOnceAndResultCombined() {
		$lexeme = NewLexeme::havingId( new LexemeId( 'L98' ) )->build();

		$statementRefExtractor = $this->createMock( StatementEntityReferenceExtractor::class );
		$statementRefExtractor->expects( $this->once() )
			->method( 'extractEntityIds' )
			->with( $lexeme )
			->willReturn( [ new LexemeId( 'L1' ) ] );

		$formStatementRefExtractor = $this->createMock( FormsStatementEntityReferenceExtractor::class );
		$formStatementRefExtractor->expects( $this->once() )
			->method( 'extractEntityIds' )
			->with( $lexeme )
			->willReturn( [ new LexemeId( 'L2' ) ] );

		$senseStatementRefExtractor = $this->createMock( SensesStatementEntityReferenceExtractor::class );
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
