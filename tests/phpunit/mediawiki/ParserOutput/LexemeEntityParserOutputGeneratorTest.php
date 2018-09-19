<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ParserOutput;

use Language;
use Message;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeIntegrationTestCase;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\WikibaseRepo;

/**
 * @coversNothing
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class LexemeEntityParserOutputGeneratorTest extends WikibaseLexemeIntegrationTestCase {

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	private $itemNamespace;

	private $propertyNamespace;

	public function setUp() {
		parent::setUp();

		$this->entityStore = $this->getEntityStore();

		$repo = WikibaseRepo::getDefaultInstance();
		$namespaceLookup = $repo->getEntityNamespaceLookup();
		$this->propertyNamespace = $namespaceLookup->getEntityNamespace( 'property' );
		$this->itemNamespace = $namespaceLookup->getEntityNamespace( 'item' );
	}

	public function testParserOutputContainsLinksForEntityIdsReferencedInFormStatements() {
		$propertyId = 'P123';
		$valueItemId = 'Q42';
		$this->saveEntity( NewItem::withId( $valueItemId )->build() );
		$this->saveEntity( new Property( new PropertyId( $propertyId ), null, 'wikibase-item' ) );
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm( NewForm::any()
				->andStatement( new PropertyValueSnak(
					new PropertyId( $propertyId ),
					new EntityIdValue( new ItemId( $valueItemId ) )
				) ) )
			->build();

		$output = $this->newParserOutputGenerator()->getParserOutput( $lexeme );

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
		$this->saveEntity( new Property( new PropertyId( $propertyId ), null, 'wikibase-item' ) );
		$lexeme = NewLexeme::havingId( 'L1' )
			->withStatement( new PropertyValueSnak(
				new PropertyId( $propertyId ),
				new EntityIdValue( new ItemId( $valueItemId ) )
			) )
			->build();

		$output = $this->newParserOutputGenerator()->getParserOutput( $lexeme );

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
		$lexeme = NewLexeme::havingId( 'L1' )
			->withLanguage( $languageItemId )
			->build();

		$output = $this->newParserOutputGenerator()->getParserOutput( $lexeme );

		$this->assertArrayHasKey(
			$languageItemId,
			$output->getLinks()[$this->itemNamespace]
		);
	}

	public function testParserOutputContainsLexicalCategoryItemIdLink() {
		$lexicalCategoryItemId = 'Q321';
		$this->saveEntity( NewItem::withId( $lexicalCategoryItemId )->build() );
		$lexeme = NewLexeme::havingId( 'L1' )
			->withLexicalCategory( $lexicalCategoryItemId )
			->build();

		$output = $this->newParserOutputGenerator()->getParserOutput( $lexeme );

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
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm( NewForm::havingId( 'F1' )
				->andGrammaticalFeature( $grammaticalFeatureItemId1 )
				->andGrammaticalFeature( $grammaticalFeatureItemId2 ) )
			->build();

		$output = $this->newParserOutputGenerator()->getParserOutput( $lexeme );

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

		$lexeme = NewLexeme::havingId( 'L1' )
			->withLemma( 'en', 'goat' )
			->withLemma( 'fr', 'taog' )
			->build();

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $lexeme );
		$title = $parserOutput->getExtensionData( 'wikibase-meta-tags' )['title'];

		$this->assertContains( 'goat', $title );
		$this->assertContains( 'taog', $title );
		$this->assertContains(
			( new Message(
				'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma' )
			)->escaped(),
			$title
		);
	}

	private function newParserOutputGenerator() {
		return WikibaseRepo::getDefaultInstance()->getEntityParserOutputGeneratorFactory()
			->getEntityParserOutputGenerator( Language::factory( 'en' ) );
	}

}
