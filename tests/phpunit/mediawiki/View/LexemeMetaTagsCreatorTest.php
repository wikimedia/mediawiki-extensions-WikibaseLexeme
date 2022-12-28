<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use RawMessage;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Presentation\View\LexemeMetaTagsCreator;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\Tests\EntityMetaTagsCreatorTestCase;

/**
 * @license GPL-2.0-or-later
 * @covers \Wikibase\Lexeme\Presentation\View\LexemeMetaTagsCreator
 */
class LexemeMetaTagsCreatorTest extends EntityMetaTagsCreatorTestCase {

	public function provideTestGetMetaTags() {
		$labelDescriptionLookup = $this->createMock( FallbackLabelDescriptionLookup::class );

		$lexemeMetaTags = new LexemeMetaTagsCreator( '/', $labelDescriptionLookup );

		$languageItemId = new ItemId( 'Q123' );
		$languageTerm = new TermFallback( 'en', 'The language', 'en', null );

		$categoryItemId = new ItemId( 'Q321' );
		$categoryTerm = new TermFallback( 'en', 'The category', 'en', null );

		$labelDescriptionLookup->method( 'getLabel' )->will(
			$this->returnValueMap( [
				[ $languageItemId, $languageTerm ],
				[ $categoryItemId, $categoryTerm ]
			] )
		);

		return [
			[
				$lexemeMetaTags,
				new Lexeme(
					new LexemeId( 'L84389' ),
					new TermList( [ new Term( 'en', 'goat' ) ] ),
					new ItemId( 'Q999' ),
					new ItemId( 'Q9999' )
				),
				[
					'title' => 'goat',
					'og:title' => 'goat',
					'twitter:card' => 'summary'
				]
			],
			[
				$lexemeMetaTags,
				new Lexeme(
					new LexemeId( 'L84389' ),
					new TermList( [ new Term( 'en', 'goat' ), new Term( 'fr', 'taog' ) ] ),
					$categoryItemId,
					$languageItemId
				),
				[
					'title' => 'goat/taog',
					'og:title' => 'goat/taog',
					'description' => 'The language The category',
					'og:description' => 'The language The category',
					'twitter:card' => 'summary'
				]
			]

		];
	}

	/**
	 * @dataProvider nonStringProvider
	 */
	public function testGivenNotAString_constructorThrowsException( $input ) {
		$this->expectException( InvalidArgumentException::class );
		new LexemeMetaTagsCreator(
			$input,
			WikibaseRepo::getFallbackLabelDescriptionLookupFactory()
				->newLabelDescriptionLookup(
					MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' )
				)
		);
	}

	public function nonStringProvider() {
		yield [ false ];
		yield [ 123 ];
		yield [ new RawMessage( 'potato' ) ];
	}

}
