<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGloss;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseEdit;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\MediaWiki\Api\EditSenseElementsRequest;
use Wikibase\Repo\ChangeOp\ChangeOps;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\EditSenseElementsRequest
 *
 * @license GPL-2.0-or-later
 */
class EditSenseElementsRequestTest extends TestCase {

	public function testReturnsChangeOpThatChangesSenseElements() {
		$senseId = new SenseId( 'L1-S1' );
		$oldGloss = new Term( 'en', 'furry animal' );
		$newGloss = new Term( 'en', 'a furry animal' );

		$sense = new Sense( $senseId, new TermList( [ $oldGloss ] ) );

		$request = new EditSenseElementsRequest(
			new SenseId( 'L1-S1' ),
			new ChangeOpSenseEdit( [
				new ChangeOps( new ChangeOpGloss( $newGloss ) ),
			] ),
			1234
		);

		$changeOp = $request->getChangeOp();

		$changeOp->apply( $sense );

		$this->assertEquals( $newGloss, $sense->getGlosses()->getByLanguage( 'en' ) );
	}

	public function testGetBaseRevId() {
		$gloss = new Term( 'en', 'a furry animal' );
		$request = new EditSenseElementsRequest(
			new SenseId( 'L1-S1' ),
			new ChangeOpSenseEdit( [
				new ChangeOps( new ChangeOpGloss( $gloss ) ),
			] ),
			1234
		);

		$this->assertSame( 1234, $request->getBaseRevId() );
	}

}
