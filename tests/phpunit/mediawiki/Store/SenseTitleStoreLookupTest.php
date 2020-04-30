<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Store;

use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\Lexeme\DataAccess\Store\SenseTitleStoreLookup;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\SenseTitleStoreLookup
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class SenseTitleStoreLookupTest extends TestCase {

	public function testGivenLexemeId_getTitleForIdFails() {
		$instance = new SenseTitleStoreLookup( $this->createMock( EntityTitleStoreLookup::class ) );

		$this->expectException( ParameterTypeException::class );
		$instance->getTitleForId( new LexemeId( 'L1' ) );
	}

	public function testGivenSenseId_getTitleForIdCallsParentServiceWithLexemeId() {
		$lexemeId = new LexemeId( 'L1' );
		$senseId = new SenseId( 'L1-S1' );

		$title = $this->createMock( Title::class );
		$title->method( 'setFragment' )
			->with( '#' . $senseId->getIdSuffix() );

		$parentLookup = $this->createMock( EntityTitleStoreLookup::class );
		$parentLookup->method( 'getTitleForId' )
			->with( $lexemeId )
			->willReturn( $title );

		$instance = new SenseTitleStoreLookup( $parentLookup );

		$result = $instance->getTitleForId( $senseId );
		$this->assertSame( $title, $result );
	}

	public function testGivenNoTitleForLexeme_getTitleForIdReturnsNull() {
		$parentLookup = $this->createMock( EntityTitleStoreLookup::class );
		$parentLookup->method( 'getTitleForId' )
			->willReturn( null );

		$lookup = new SenseTitleStoreLookup( $parentLookup );

		$this->assertNull( $lookup->getTitleForId( new SenseId( 'L66-S1' ) ) );
	}

}
