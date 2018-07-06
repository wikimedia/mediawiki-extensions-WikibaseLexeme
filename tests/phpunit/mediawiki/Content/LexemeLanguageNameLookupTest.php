<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Content;

use IContextSource;
use Wikibase\Lexeme\Content\LexemeLanguageNameLookup;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Wikibase\Lexeme\Content\LexemeLanguageNameLookup
 *
 * @license GPL-2.0-or-later
 */
class LexemeLanguageNameLookupTest extends TestCase {

	public function testGetNameDelegatedToParentForDefaultLanguages() {
		$messageLocalizer = $this->getMockBuilder( IContextSource::class )->getMock();
		$messageLocalizer->expects( $this->never() )->method( 'msg' );
		$lookup = new LexemeLanguageNameLookup( null, $messageLocalizer, [] );

		$this->assertInternalType( 'string', $lookup->getName( 'en' ) );
	}

	public function testGetNameUsesMessageLocalizerToFindLanaguageName() {
		$messageLocalizer = $this->getMockBuilder( IContextSource::class )->getMock();
		$messageLocalizer->expects( $this->once() )
			->method( 'msg' )
			->with( 'wikibase-lexeme-language-name-en' )
			->willReturn( 'British 🍵' );
		$lookup = new LexemeLanguageNameLookup( null, $messageLocalizer, [ 'en' ] );

		$this->assertSame( 'British 🍵', $lookup->getName( 'en' ) );
	}

}
