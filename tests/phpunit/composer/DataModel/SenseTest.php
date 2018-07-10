<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DataModel\SenseId;

/**
 * @covers \Wikibase\Lexeme\DataModel\Sense
 *
 * @license GPL-2.0-or-later
 */
class SenseTest extends TestCase {

	public function testCanBeCreated() {
		$sense = new Sense( new SenseId( 'L1-S1' ), new TermList(), new StatementList() );

		$this->assertSame( 'L1-S1', $sense->getId()->getSerialization() );
		$this->assertTrue( $sense->getGlosses()->isEmpty() );
		$this->assertTrue( $sense->getStatements()->isEmpty() );
	}

	public function testCopyClones() {
		$sense = new Sense( new SenseId( 'L1-S1' ), new TermList(), new StatementList() );
		$copy = $sense->copy();

		$this->assertNotSame( $sense->getGlosses(), $copy->getGlosses() );
		$this->assertNotSame( $sense->getStatements(), $copy->getStatements() );
	}

}
