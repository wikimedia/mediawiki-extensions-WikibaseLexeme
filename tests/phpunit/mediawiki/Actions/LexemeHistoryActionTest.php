<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\mediawiki;

use Article;
use IContextSource;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\Store\EntityLookupLemmaLookup;
use Wikibase\Lexeme\DataAccess\Store\LemmaLookup;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\Actions\LexemeHistoryAction;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikimedia\TestingAccessWrapper;
use WikiPage;

/**
 * @license GPL-2.0-or-later
 * @covers LexemeHistoryAction
 */
final class LexemeHistoryActionTest extends TestCase {

	private const SEP_MESSAGE_KEY = 'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma';

	public function testFallsBackToParentIfNoLexemeId(): void {
		$fakeEntityIdLookup = $this->createStub( EntityIdLookup::class );
		$fakeEntityIdLookup->method( 'getEntityIdForTitle' )->willReturn( null );
		$mockLemmaLookup = $this->createMock( LemmaLookup::class );
		$mockLemmaLookup->expects( $this->never() )->method( 'getLemmas' );

		$lexemeHistoryAction = $this->getLexemeHistoryAction( $fakeEntityIdLookup, $mockLemmaLookup );
		$actualTitle = $lexemeHistoryAction->getPageTitle();

		$this->assertSame( '(history-title: Page title)', $actualTitle );
	}

	public function testReturnsLemmasInTitleProperlyEscaped(): void {
		$lexemeId = new LexemeId( 'L1' );
		$lemmas = new TermList( [
			new Term( 'en', '<script>alert(\'Escape me!\');</script>' ),
			new Term( 'en-gb', 'colour' ),
		] );
		$lexeme = new Lexeme( $lexemeId, $lemmas );
		$fakeEntityLookup = $this->createMock( EntityLookup::class );
		$fakeEntityLookup->method( 'getEntity' )->willReturn( $lexeme );
		$lemmaLookup = new EntityLookupLemmaLookup( $fakeEntityLookup );
		$fakeEntityIdLookup = $this->createStub( EntityIdLookup::class );
		$fakeEntityIdLookup->method( 'getEntityIdForTitle' )->willReturn( $lexemeId );

		$lexemeHistoryAction = $this->getLexemeHistoryAction( $fakeEntityIdLookup, $lemmaLookup );
		$actualTitle = $lexemeHistoryAction->getPageTitle();

		$separatorMessageKey = self::SEP_MESSAGE_KEY;
		// phpcs:ignore Generic.Files.LineLength.TooLong
		$expectedTitle = "(wikibase-history-title-with-label: L1, <span class=\"mw-content-ltr\" dir=\"ltr\" lang=\"en\">&lt;script>alert('Escape me!');&lt;/script></span>($separatorMessageKey)<span class=\"mw-content-ltr\" dir=\"ltr\" lang=\"en-GB\">colour</span>)";
		$this->assertSame( $expectedTitle, $actualTitle );
	}

	private function getLexemeHistoryAction(
		EntityIdLookup $entityIdLookup,
		LemmaLookup $lemmaLookup
	) {
		$context = $this->getContext();
		return TestingAccessWrapper::newFromObject(
			new LexemeHistoryAction(
				$this->getArticle(),
				$context,
				$entityIdLookup,
				$lemmaLookup,
				new LexemeTermFormatter( $context
					->msg( self::SEP_MESSAGE_KEY )
					->escaped() )
			)
		);
	}

	private function getContext() {
		$fakeContext = $this->createStub( IContextSource::class );
		$fakeContext->method( 'msg' )->willReturnCallback( static function ( ...$params ) {
			return wfMessage( ...$params )->inLanguage( 'qqx' );
		} );
		return $fakeContext;
	}

	private function getArticle(): Article {
		$title = Title::newFromTextThrow( 'Page title' );
		$article = $this->createMock( Article::class );
		$article->method( 'getTitle' )
			->willReturn( $title );
		$article->method( 'getPage' )
			->willReturn( $this->getWikiPage( $title ) );

		return $article;
	}

	private function getWikiPage( Title $title ): WikiPage {
		$wikiPage = $this->createMock( WikiPage::class );
		$wikiPage->method( 'getTitle' )
			->willReturn( $title );

		return $wikiPage;
	}
}
