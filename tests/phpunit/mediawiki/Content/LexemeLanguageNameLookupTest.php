<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Content;

use IContextSource;
use Message;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookup;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookup
 *
 * @license GPL-2.0-or-later
 */
class LexemeLanguageNameLookupTest extends TestCase {

	public function testGetNameDelegatedToFallbackForDefaultLanguages() {
		$messageLocalizer = $this->getMockBuilder( IContextSource::class )->getMock();
		$messageLocalizer->expects( $this->never() )->method( 'msg' );

		$fallbackLookup = $this->getMockBuilder( LanguageNameLookup::class )->getMock();
		$fallbackLookup->expects( $this->once() )
			->method( 'getName' )
			->with( 'en' )
			->willReturn( 'American' );

		$lookup = new LexemeLanguageNameLookup( $messageLocalizer, [], $fallbackLookup );

		$this->assertSame( 'American', $lookup->getName( 'en' ) );
	}

	public function testGetNameUsesMessageLocalizerToFindLanguageName() {
		$message = $this->getMockBuilder( Message::class )->disableOriginalConstructor()->getMock();
		$message->expects( $this->once() )
			->method( 'plain' )
			->willReturn( 'British 🍵' );

		$messageLocalizer = $this->getMockBuilder( IContextSource::class )->getMock();
		$messageLocalizer->expects( $this->once() )
			->method( 'msg' )
			->with( 'wikibase-lexeme-language-name-en' )
			->willReturn( $message );

		$fallbackLookup = $this->getMockBuilder( LanguageNameLookup::class )->getMock();
		$fallbackLookup->expects( $this->never() )->method( 'getName' );

		$lookup = new LexemeLanguageNameLookup( $messageLocalizer, [ 'en' ], $fallbackLookup );

		$this->assertSame( 'British 🍵', $lookup->getName( 'en' ) );
	}

}
