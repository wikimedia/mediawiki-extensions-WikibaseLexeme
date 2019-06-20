<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveForm;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\MediaWiki\Api\RemoveFormRequest;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\RemoveFormRequest
 *
 * @license GPL-2.0-or-later
 */
class RemoveFormRequestTest extends TestCase {

	public function testReturnsCorrectChangeOp() {
		$request = new RemoveFormRequest( new FormId( 'L1-F1' ), 3 );

		$changeOp = $request->getChangeOp();
		$this->assertTrue( $changeOp instanceof ChangeOpRemoveForm );
	}

	public function testGetLexemeId() {
		$request = new RemoveFormRequest( new FormId( 'L1-F1' ), 3 );

		$this->assertSame( 'L1-F1', $request->getFormId()->getSerialization() );
	}

	public function testGetBaseRevId() {
		$request = new RemoveFormRequest( new FormId( 'L1-F1' ), 3 );

		$this->assertSame( 3, $request->getBaseRevId() );
	}

}
