<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks\Formatters;

use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeIntegrationTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\EntityLinkFormatters\FormLinkFormatter
 *
 * @license GPL-2.0-or-later
 */
class FormLinkFormatterIntegrationTest extends WikibaseLexemeIntegrationTestCase {

	public function testFormLinkFormatter() {
		$representation = 'Kartoffel';
		$formId = new FormId( 'L321-F1' );
		$representationLanguage = 'de';
		$lexeme = NewLexeme::havingId( 'L321' )
			->withForm( NewForm::havingId( $formId )
				->andRepresentation( $representationLanguage, $representation ) )
			->build();
		$this->saveEntity( $lexeme );

		$this->assertThatHamcrest(
			$this->getLinkFormatter( $formId->getEntityType() )->getHtml( $formId ),
			is( htmlPiece( both( havingChild( allOf(
				withTagName( 'span' ),
				withAttribute( 'lang' )->havingValue( $representationLanguage ),
				withAttribute( 'dir' )->havingValue( 'ltr' ),
				havingTextContents( $representation )
			) ) )->andAlso(
				havingChild( havingTextContents( containsString( $formId->getSerialization() ) ) )
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

	private function getEntityTitleTextLookupMock( string $titleText = null ) {
		$entityTitleTextLookup = $this->createMock( EntityTitleTextLookup::class );
		$entityTitleTextLookup->method( 'getPrefixedText' )
			->with( $entityId ?? $this->anything() )
			->willReturn( $titleText );
		return $entityTitleTextLookup;
	}

}
