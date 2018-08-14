<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\PropertyType;

use MediaWikiLangTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Title;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lexeme\PropertyType\SenseIdHtmlFormatter;
use Wikibase\Lexeme\PropertyType\SenseIdTextFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * @covers \Wikibase\Lexeme\PropertyType\SenseIdHtmlFormatter
 *
 * @license GPL-2.0-or-later
 */
class SenseIdHtmlFormatterTest extends MediaWikiLangTestCase {

	/**
	 * @param FormId $expectedSenseId
	 * @return MockObject|EntityTitleLookup
	 */
	private function getMockTitleLookup( SenseId $expectedSenseId ) {
		$title = $this->getMock( Title::class );
		$title->method( 'isLocal' )->willReturn( true );
		$title->method( 'getLinkUrl' )->willReturn( 'LOCAL-URL#FORM' );

		/** @var EntityTitleLookup|MockObject $titleLookup */
		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->method( 'getTitleForId' )
			->with( $expectedSenseId )
			->willReturn( $title );

		return $titleLookup;
	}

	/**
	 * @param SenseId $expectedSenseId
	 * @param string $return
	 * @return MockObject|SenseIdTextFormatter
	 */
	private function getMockTextFormatter( SenseId $expectedSenseId, $return ) {
		/** @var SenseIdTextFormatter|MockObject $textFormatter */
		$textFormatter = $this->getMock(
			SenseIdTextFormatter::class,
			[],
			[],
			'',
			false // do not call original constructor
		);
		$textFormatter->method( 'formatEntityId' )
			->with( $expectedSenseId )
			->willReturn( $return );

		return $textFormatter;
	}

	public function testFormatEntityId_wrapsTextFormatter() {
		$senseId = new SenseId( 'L999-S666' );
		$formatter = new SenseIdHtmlFormatter(
			$this->getMockTextFormatter( $senseId, 'foo bar baz' ),
			$this->getMockTitleLookup( $senseId )
		);

		$result = $formatter->formatEntityId( $senseId );

		$this->assertSame(
			'<a href="LOCAL-URL#FORM">foo bar baz</a>',
			$result
		);
	}

	public function testFormatEntityId_htmlEscapesText() {
		$senseId = new SenseId( 'L999-S666' );
		$formatter = new SenseIdHtmlFormatter(
			$this->getMockTextFormatter( $senseId, '<script>alert("hi")</script>' ),
			$this->getMockTitleLookup( $senseId )
		);

		$result = $formatter->formatEntityId( $senseId );

		$this->assertSame(
			'<a href="LOCAL-URL#FORM">&lt;script>alert("hi")&lt;/script></a>',
			$result
		);
	}

}
