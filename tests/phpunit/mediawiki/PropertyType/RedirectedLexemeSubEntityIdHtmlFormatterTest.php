<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\PropertyType;

use HamcrestPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit4And6Compat;
use Title;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\LexemeSubEntityId;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lexeme\PropertyType\RedirectedLexemeSubEntityIdHtmlFormatter;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * @covers \Wikibase\Lexeme\PropertyType\RedirectedLexemeSubEntityIdHtmlFormatter
 *
 * @license GPL-2.0-or-later
 */
class RedirectedLexemeSubEntityIdHtmlFormatterTest extends TestCase {

	use HamcrestPHPUnitIntegration;
	use PHPUnit4And6Compat;

	/**
	 * @var EntityTitleLookup|MockObject
	 */
	private $entityTitleLookup;

	public function setUp() {
		parent::setUp();

		$this->entityTitleLookup = $this->createMock( EntityTitleLookup::class );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testAssertsCorrectInputType() {
		$formatter = $this->newFormatter();
		$formatter->formatEntityId( new LexemeId( 'L1337' ) );
	}

	/**
	 * @dataProvider provideSubEntityInput
	 */
	public function testCanFormatsSubEntityIdsWithLocalLinkUrl(
		$expectedAnchorText,
		$expectedUrlSuffix,
		LexemeSubEntityId $subEntityId
	) {
		$title = $this->createMock( Title::class );
		$title->method( 'isLocal' )->willReturn( true );
		$title->expects( $this->once() )
			->method( 'getLinkURL' )
			->willReturn( '/Lexeme:' . $expectedUrlSuffix );

		$this->entityTitleLookup
			->expects( $this->once() )
			->method( 'getTitleForId' )
			->with( $subEntityId )
			->willReturn( $title );

		$formatter = $this->newFormatter();
		$formattedId = $formatter->formatEntityId( $subEntityId );

		$this->assertThatHamcrest(
			$formattedId,
			is( htmlPiece( havingRootElement(
				both(
					tagMatchingOutline( '<a href="/Lexeme:' . $expectedUrlSuffix . '"/>' )
				)->andAlso( havingTextContents( $expectedAnchorText ) )
			) ) )
		);
	}

	/**
	 * @dataProvider provideSubEntityInput
	 */
	public function testCanFormatsSubEntityIdsWithFullLinkUrl(
		$expectedAnchorText,
		$expectedUrlSuffix,
		LexemeSubEntityId $subEntityId
	) {
		$title = $this->createMock( Title::class );
		$title->method( 'isLocal' )->willReturn( false );
		$title->expects( $this->once() )
			->method( 'getFullURL' )
			->willReturn( 'http://url.for/Lexeme:' . $expectedUrlSuffix );

		$this->entityTitleLookup
			->expects( $this->once() )
			->method( 'getTitleForId' )
			->with( $subEntityId )
			->willReturn( $title );

		$formatter = $this->newFormatter();
		$formattedId = $formatter->formatEntityId( $subEntityId );

		$this->assertThatHamcrest(
			$formattedId,
			is( htmlPiece( havingRootElement(
				both(
					tagMatchingOutline( '<a href="http://url.for/Lexeme:' . $expectedUrlSuffix . '"/>' )
				)->andAlso( havingTextContents( $expectedAnchorText ) )
			) ) )
		);
	}

	public function provideSubEntityInput() {
		yield [ 'L9-F2', 'L9#L9-F2', new FormId( 'L9-F2' ) ];
		yield [ 'L9-S7', 'L9#L9-S7', new SenseId( 'L9-S7' ) ];
	}

	private function newFormatter() {
		return new RedirectedLexemeSubEntityIdHtmlFormatter( $this->entityTitleLookup );
	}

}
