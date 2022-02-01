<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGloss;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGlossList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseEdit;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\Api\AddSenseRequest;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\AddSenseRequest
 *
 * @license GPL-2.0-or-later
 */
class AddSenseRequestTest extends TestCase {

	public function testReturnsChangeOpThatAddsSense() {
		$request = new AddSenseRequest(
			new LexemeId( 'L1' ),
			new ChangeOpSenseEdit( [
				new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'furry animal' ) ) ] ),
			] ),
			1
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
			] ),
			1
		);

		$this->assertSame( $lexemeId, $request->getLexemeId() );
	}

	public function testGetBaseRevId() {
		$request = new AddSenseRequest(
			new LexemeId( 'L1' ),
			new ChangeOpSenseEdit( [
				new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'furry animal' ) ) ] ),
			] ),
			1
		);

		$this->assertSame( 1, $request->getBaseRevId() );
	}

}
