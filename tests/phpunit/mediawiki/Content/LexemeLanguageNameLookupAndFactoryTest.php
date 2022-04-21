<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\Content;

use IContextSource;
use Language;
use Message;
use MessageLocalizer;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookupFactory;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\LanguageNameLookupFactory;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookup
 * @covers \Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookupFactory
 *
 * @license GPL-2.0-or-later
 */
class LexemeLanguageNameLookupAndFactoryTest extends TestCase {

	public function testGetNameDelegatedToFallbackForDefaultLanguages() {
		$contextSource = $this->createMock( IContextSource::class );
		$contextSource->expects( $this->never() )->method( 'msg' );
		$language = $this->createMock( Language::class );
		$language->method( 'getCode' )->willReturn( 'en' );
		$contextSource->method( 'getLanguage' )
			->willReturn( $language );

		$fallbackLookup = $this->createMock( LanguageNameLookup::class );
		$fallbackLookup->method( 'getName' )
			->with( 'en' )
			->willReturn( 'American' );
		$fallbackLookupFactory = $this->createMock( LanguageNameLookupFactory::class );
		$fallbackLookupFactory->method( 'getForLanguageCode' )
			->with( 'en' )
			->willReturn( $fallbackLookup );

		$factory = new LexemeLanguageNameLookupFactory( $fallbackLookupFactory, [] );
		$lookup = $factory->getForContextSource( $contextSource );

		$this->assertSame( 'American', $lookup->getName( 'en' ) );
	}

	public function testGetNameUsesMessageLocalizerToFindLanguageName() {
		$message = $this->createMock( Message::class );
		$message->expects( $this->once() )
			->method( 'plain' )
			->willReturn( 'British 🍵' );

		$messageLocalizer = $this->createMock( MessageLocalizer::class );
		$messageLocalizer->expects( $this->once() )
			->method( 'msg' )
			->with( 'wikibase-lexeme-language-name-en' )
			->willReturn( $message );

		$fallbackLookup = $this->createMock( LanguageNameLookup::class );
		$fallbackLookup->expects( $this->never() )->method( 'getName' );
		$fallbackLookupFactory = $this->createMock( LanguageNameLookupFactory::class );
		$fallbackLookupFactory->method( 'getForLanguageCode' )
			->with( 'en' )
			->willReturn( $fallbackLookup );

		$factory = new LexemeLanguageNameLookupFactory( $fallbackLookupFactory, [ 'en' ] );
		$lookup = $factory->getForLanguageCodeAndMessageLocalizer( 'en', $messageLocalizer );

		$this->assertSame( 'British 🍵', $lookup->getName( 'en' ) );
	}

}
