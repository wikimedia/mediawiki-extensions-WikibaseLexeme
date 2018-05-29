<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks\Formatters;

use Language;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Hooks\Formatters\LexemeLinkFormatter;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatter;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers Wikibase\Lexeme\Hooks\Formatters\LexemeLinkFormatter
 *
 * @license GPL-2.0-or-later
 */
class LexemeLinkFormatterTest extends TestCase {

	use \PHPUnit4And6Compat;

	/**
	 * @dataProvider notALexemeProvider
	 */
	public function testGivenNotALexemeId_getHtmlThrowsException( EntityId $id ) {
		$formatter = $this->getLexemeLinkFormatter();
		$this->setExpectedException( ParameterTypeException::class );
		$formatter->getHtml( $id, [] );
	}

	public function testGetHtml() {
		$lookup = $this->getMockEntityLookupReturningLexeme(
			NewLexeme::havingId( 'L2' )->withLemma( 'en', 'potato' )->build()
		);
		$formatter = new LexemeLinkFormatter(
			$lookup,
			$this->getMockDefaultFormatter(),
			$this->getMockMessageLocalizer(),
			Language::factory( 'en' )
		);

		$this->assertEquals(
			'L2 en: potato',
			$formatter->getHtml( new LexemeId( 'L2' ) )
		);
	}

	public function testGivenMultipleLemmas_getHtmlConcatenatesThem() {
		$lookup = $this->getMockEntityLookupReturningLexeme(
			NewLexeme::havingId( 'L321' )
				->withLemma( 'en-gb', 'colour' )
				->withLemma( 'en-ca', 'color' )
				->build()
		);
		$formatter = new LexemeLinkFormatter(
			$lookup,
			$this->getMockDefaultFormatter(),
			$this->getMockMessageLocalizer(),
			Language::factory( 'en' )
		);

		$this->assertEquals(
			'L321 en: colour/color',
			$formatter->getHtml( new LexemeId( 'L321' ) )
		);
	}

	private function getLexemeLinkFormatter() {
		return new LexemeLinkFormatter(
			$this->getMock( EntityLookup::class ),
			$this->getMockDefaultFormatter(),
			$this->getMockMessageLocalizer(),
			Language::factory( 'en' )
		);
	}

	public function notALexemeProvider() {
		return [
			[ new ItemId( 'Q123' ) ],
			[ new FormId( 'L12-F3' ) ],
		];
	}

	public function getMockEntityLookupReturningLexeme( Lexeme $lexeme ) {
		$entityLookup = $this->getMock( EntityLookup::class );
		$entityLookup->method( 'getEntity' )->willReturn( $lexeme );

		return $entityLookup;
	}

	public function testGetTitleAttribute() {
		$formatter = $this->getLexemeLinkFormatter();
		$title = $this->getMock( Title::class );
		$title->method( 'getPrefixedText' )->willReturn( 'Lexeme:L123' );
		$this->assertEquals(
			'Lexeme:L123',
			$formatter->getTitleAttribute( $title )
		);
	}

	/**
	 * @return EntityLinkFormatter|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getMockDefaultFormatter() {
		$formatter = $this->getMockBuilder( DefaultEntityLinkFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$formatter->method( 'getHtml' )
			->willReturnCallback( function ( EntityId $entityId, array $label ) {
				return $entityId->getSerialization() . " ${label['language']}: ${label['value']}";
			} );

		return $formatter;
	}

	private function getMockMessageLocalizer() {
		$localizer = $this->getMock( \MessageLocalizer::class );
		$localizer->method( 'msg' )->willReturn( new \RawMessage( '/' ) );

		return $localizer;
	}

}
