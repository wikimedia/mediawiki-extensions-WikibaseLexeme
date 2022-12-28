<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ParserOutput;

use DataValues\StringValue;
use Message;
use ParserOutput;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeIntegrationTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\ParserOutput\EntityParserOutputGenerator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @coversNothing
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class LexemeEntityParserOutputGeneratorTest extends WikibaseLexemeIntegrationTestCase {

	private $itemNamespace;

	private $propertyNamespace;

	protected function setUp(): void {
		parent::setUp();

		$namespaceLookup = WikibaseRepo::getEntityNamespaceLookup();
		$this->propertyNamespace = $namespaceLookup->getEntityNamespace( 'property' );
		$this->itemNamespace = $namespaceLookup->getEntityNamespace( 'item' );
	}

	public function testParserOutputContainsLinksForEntityIdsReferencedInFormStatements() {
		$propertyId = 'P123';
		$valueItemId = 'Q42';
		$this->saveEntity( NewItem::withId( $valueItemId )->build() );
		$this->saveEntity( new Property( new NumericPropertyId( $propertyId ), null, 'wikibase-item' ) );
		$entityRevision = new EntityRevision( NewLexeme::havingId( 'L1' )
			->withForm( NewForm::any()
				->andStatement( new PropertyValueSnak(
					new NumericPropertyId( $propertyId ),
					new EntityIdValue( new ItemId( $valueItemId ) )
				) ) )
			->build() );

		$output = $this->newParserOutputGenerator()->getParserOutput( $entityRevision );

		$this->assertArrayHasKey(
			$propertyId,
			$output->getLinks()[$this->propertyNamespace]
		);
		$this->assertArrayHasKey(
			$valueItemId,
			$output->getLinks()[$this->itemNamespace]
		);
	}

	public function testParserOutputContainsLinksForEntityIdsReferencedInStatements() {
		$propertyId = 'P123';
		$valueItemId = 'Q42';
		$this->saveEntity( NewItem::withId( $valueItemId )->build() );
		$this->saveEntity( new Property( new NumericPropertyId( $propertyId ), null, 'wikibase-item' ) );
		$entityRevision = new EntityRevision( NewLexeme::havingId( 'L1' )
			->withStatement( new PropertyValueSnak(
				new NumericPropertyId( $propertyId ),
				new EntityIdValue( new ItemId( $valueItemId ) )
			) )
			->build() );

		$output = $this->newParserOutputGenerator()->getParserOutput( $entityRevision );

		$this->assertArrayHasKey(
			$propertyId,
			$output->getLinks()[$this->propertyNamespace]
		);
		$this->assertArrayHasKey(
			$valueItemId,
			$output->getLinks()[$this->itemNamespace]
		);
	}

	public function testParserOutputContainsLanguageItemIdLink() {
		$languageItemId = 'Q123';
		$this->saveEntity( NewItem::withId( $languageItemId )->build() );
		$entityRevision = new EntityRevision( NewLexeme::havingId( 'L1' )
			->withLanguage( $languageItemId )
			->build() );

		$output = $this->newParserOutputGenerator()->getParserOutput( $entityRevision );

		$this->assertArrayHasKey(
			$languageItemId,
			$output->getLinks()[$this->itemNamespace]
		);
	}

	public function testParserOutputContainsLexicalCategoryItemIdLink() {
		$lexicalCategoryItemId = 'Q321';
		$this->saveEntity( NewItem::withId( $lexicalCategoryItemId )->build() );
		$entityRevision = new EntityRevision( NewLexeme::havingId( 'L1' )
			->withLexicalCategory( $lexicalCategoryItemId )
			->build() );

		$output = $this->newParserOutputGenerator()->getParserOutput( $entityRevision );

		$this->assertArrayHasKey(
			$lexicalCategoryItemId,
			$output->getLinks()[$this->itemNamespace]
		);
	}

	public function testParserOutputContainsGrammaticalFeatureItemIdLinks() {
		$grammaticalFeatureItemId1 = 'Q234';
		$grammaticalFeatureItemId2 = 'Q432';
		$this->saveEntity( NewItem::withId( $grammaticalFeatureItemId1 )->build() );
		$this->saveEntity( NewItem::withId( $grammaticalFeatureItemId2 )->build() );
		$entityRevision = new EntityRevision( NewLexeme::havingId( 'L1' )
			->withForm( NewForm::havingId( 'F1' )
				->andGrammaticalFeature( $grammaticalFeatureItemId1 )
				->andGrammaticalFeature( $grammaticalFeatureItemId2 ) )
			->build() );

		$output = $this->newParserOutputGenerator()->getParserOutput( $entityRevision );

		$this->assertArrayHasKey(
			$grammaticalFeatureItemId1,
			$output->getLinks()[$this->itemNamespace]
		);
		$this->assertArrayHasKey(
			$grammaticalFeatureItemId2,
			$output->getLinks()[$this->itemNamespace]
		);
	}

	public function testTitleText_Lemma() {
		$entityParserOutputGenerator = $this->newParserOutputGenerator();

		$entityRevision = new EntityRevision( NewLexeme::havingId( 'L1' )
			->withLemma( 'en', 'goat' )
			->withLemma( 'fr', 'taog' )
			->build() );

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $entityRevision );
		$title = $parserOutput->getExtensionData( 'wikibase-meta-tags' )['title'];

		$this->assertStringContainsString( 'goat', $title );
		$this->assertStringContainsString( 'taog', $title );
		$this->assertStringContainsString(
			( new Message(
				'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma' )
			)->escaped(),
			$title
		);
	}

	private function newParserOutputGenerator(): EntityParserOutputGenerator {
		return WikibaseRepo::getEntityParserOutputGeneratorFactory()
			->getEntityParserOutputGenerator(
				$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' )
			);
	}

	/**
	 * This test assumes ExternalLinksDataUpdater is applied to all statements.
	 */
	public function testLexemeStatementsAreProcessed() {
		$this->assertParserOutputContainsUrl(
			'http://example.com',
			$this->newParserOutputGenerator()->getParserOutput(
				new EntityRevision( $this->newLexemeWithUrlInStatement( 'http://example.com' ) )
			)
		);
	}

	private function newLexemeWithUrlInStatement( $url ): Lexeme {
		return NewLexeme::havingId( 'L1' )
			->withStatement( $this->newUrlSnak( $url ) )->build();
	}

	private function newUrlSnak( $url ): PropertyValueSnak {
		return new PropertyValueSnak(
			$this->getIdOfUrlProperty(),
			new StringValue( $url )
		);
	}

	private function getIdOfUrlProperty(): NumericPropertyId {
		$urlPropertyId = new NumericPropertyId( 'P1' );

		$this->saveEntity(
			new Property( $urlPropertyId, null, 'url' )
		);

		return $urlPropertyId;
	}

	private function assertParserOutputContainsUrl( $url, ParserOutput $output ) {
		$this->assertArrayHasKey(
			$url,
			$output->getExternalLinks()
		);
	}

	public function testLexemeFormStatementsAreProcessed() {
		$this->assertParserOutputContainsUrl(
			'http://example.com',
			$this->newParserOutputGenerator()->getParserOutput(
				new EntityRevision( $this->newLexemeWithUrlInFormStatement( 'http://example.com' ) )
			)
		);
	}

	private function newLexemeWithUrlInFormStatement( $url ): Lexeme {
		return NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andStatement( $this->newUrlSnak( $url ) )
					->build()
			)
			->build();
	}

	public function testLexemeSenseStatementsAreProcessed() {
		$this->assertParserOutputContainsUrl(
			'http://example.com',
			$this->newParserOutputGenerator()->getParserOutput(
				new EntityRevision( $this->newLexemeWithUrlInSenseStatement( 'http://example.com' ) )
			)
		);
	}

	private function newLexemeWithUrlInSenseStatement( $url ): Lexeme {
		return NewLexeme::havingId( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withStatement( $this->newUrlSnak( $url ) )
					->build()
			)
			->build();
	}

}
