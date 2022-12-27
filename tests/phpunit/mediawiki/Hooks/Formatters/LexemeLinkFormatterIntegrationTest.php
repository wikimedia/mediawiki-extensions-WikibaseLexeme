<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks\Formatters;

use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeIntegrationTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\EntityLinkFormatters\LexemeLinkFormatter
 *
 * @license GPL-2.0-or-later
 */
class LexemeLinkFormatterIntegrationTest extends WikibaseLexemeIntegrationTestCase {

	public function testLexemeLinkFormatter() {
		$lemma = 'potato';
		$lexemeId = 'L123';
		$lemmaLanguage = 'en';
		$lexeme = NewLexeme::havingId( $lexemeId )
			->withLemma( $lemmaLanguage, $lemma )
			->build();
		$this->saveEntity( $lexeme );

		$this->assertThatHamcrest(
			$this->getLinkFormatter( $lexeme->getType() )->getHtml( $lexeme->getId() ),
			is( htmlPiece( both( havingChild( allOf(
				withTagName( 'span' ),
				withAttribute( 'lang' )->havingValue( $lemmaLanguage ),
				withAttribute( 'dir' )->havingValue( 'ltr' ),
				havingTextContents( $lemma )
			) ) )->andAlso(
				havingChild( havingTextContents( containsString( $lexemeId ) ) )
			) ) )
		);
	}

	private function getLinkFormatter( $entityType ): EntityLinkFormatter {
		$factory = WikibaseRepo::getEntityLinkFormatterFactory();

		return $factory->getLinkFormatter(
			$entityType,
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' )
		);
	}

}
