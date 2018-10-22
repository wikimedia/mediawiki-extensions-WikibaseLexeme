<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\MediaWiki\Api\AddSenseRequest;
use Wikibase\Lexeme\ChangeOp\ChangeOpGloss;
use Wikibase\Lexeme\ChangeOp\ChangeOpGlossList;
use Wikibase\Lexeme\ChangeOp\ChangeOpSenseEdit;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\AddSenseRequest
 *
 * @license GPL-2.0-or-later
 */
class AddSenseRequestTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testReturnsChangeOpThatAddsSense() {
		$request = new AddSenseRequest(
			new LexemeId( 'L1' ),
			new ChangeOpSenseEdit( [
				new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'furry animal' ) ) ] ),
			] )
		);

		$changeOp = $request->getChangeOp();

		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$changeOp->apply( $lexeme );

		$senses = $lexeme->getSenses()->toArray();

		$this->assertCount( 1, $senses );
		$this->assertSame( [ 'en' => 'furry animal' ], $senses[0]->getGlosses()->toTextArray() );
	}

	public function testGetLexemeId() {
		$lexemeId = new LexemeId( 'L1' );

		$request = new AddSenseRequest(
			$lexemeId,
			new ChangeOpSenseEdit( [
				new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'furry animal' ) ) ] ),
			] )
		);

		$this->assertSame( $lexemeId, $request->getLexemeId() );
	}

}
