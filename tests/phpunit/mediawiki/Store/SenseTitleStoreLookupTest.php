<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Store;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Title;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Store\SenseTitleStoreLookup;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\Store\SenseTitleStoreLookup
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class SenseTitleStoreLookupTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testGivenLexemeId_getTitleForIdFails() {
		$instance = new SenseTitleStoreLookup( $this->getMock( EntityTitleStoreLookup::class ) );

		$this->setExpectedException( ParameterTypeException::class );
		$instance->getTitleForId( new LexemeId( 'L1' ) );
	}

	public function testGivenSenseId_getTitleForIdCallsParentServiceWithLexemeId() {
		$lexemeId = new LexemeId( 'L1' );
		$senseId = new SenseId( 'L1-S1' );

		$title = $this->getMock( Title::class );
		$title->method( 'setFragment' )
			->with( '#' . $senseId->getIdSuffix() );

		$parentLookup = $this->getMock( EntityTitleStoreLookup::class );
		$parentLookup->method( 'getTitleForId' )
			->with( $lexemeId )
			->willReturn( $title );

		$instance = new SenseTitleStoreLookup( $parentLookup );

		$result = $instance->getTitleForId( $senseId );
		$this->assertSame( $title, $result );
	}

	public function testGivenNoTitleForLexeme_getTitleForIdReturnsNull() {
		$parentLookup = $this->getMock( EntityTitleStoreLookup::class );
		$parentLookup->method( 'getTitleForId' )
			->willReturn( null );

		$lookup = new SenseTitleStoreLookup( $parentLookup );

		$this->assertNull( $lookup->getTitleForId( new SenseId( 'L66-S1' ) ) );
	}

}
