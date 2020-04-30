<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveSense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\MediaWiki\Api\RemoveSenseRequest;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\RemoveSenseRequest
 *
 * @license GPL-2.0-or-later
 */
class RemoveSenseRequestTest extends TestCase {

	public function testReturnsCorrectChangeOp() {
		$request = new RemoveSenseRequest( new SenseId( 'L1-S1' ), 3 );

		$changeOp = $request->getChangeOp();
		$this->assertTrue( $changeOp instanceof ChangeOpRemoveSense );
	}

	public function testGetLexemeId() {
		$request = new RemoveSenseRequest( new SenseId( 'L1-S1' ), 2 );

		$this->assertSame( 'L1-S1', $request->getSenseId()->getSerialization() );
	}

	public function testGetBaseRevId() {
		$request = new RemoveSenseRequest( new SenseId( 'L1-S1' ), 2 );

		$this->assertEquals( 2, $request->getBaseRevId() );
	}

}
