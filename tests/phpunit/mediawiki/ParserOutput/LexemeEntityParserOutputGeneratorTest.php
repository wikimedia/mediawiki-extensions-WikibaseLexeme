<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ParserOutput;

use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeIntegrationTestCase;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\WikibaseRepo;

/**
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

		$repo = WikibaseRepo::getDefaultInstance();
		$this->entityStore = $repo->getEntityStore();

		$namespaceLookup = $repo->getEntityNamespaceLookup();
		$this->propertyNamespace = $namespaceLookup->getEntityNamespace( 'property' );
		$this->itemNamespace = $namespaceLookup->getEntityNamespace( 'item' );
	}

	public function testParserOutputContainsLinksForEntityIdsReferencedInFormStatements() {
		$propertyId = 'P123';
		$valueItemId = 'Q42';
		$this->saveItem( $valueItemId );
		$this->saveProperty( $propertyId );
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
		$this->saveItem( $valueItemId );
		$this->saveProperty( $propertyId );
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
		$this->saveItem( $languageItemId );
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
		$this->saveItem( $lexicalCategoryItemId );
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
		$this->saveItem( $grammaticalFeatureItemId1 );
		$this->saveItem( $grammaticalFeatureItemId2 );
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

	private function newParserOutputGenerator() {
		return WikibaseRepo::getDefaultInstance()->getEntityParserOutputGeneratorFactory()
			->getEntityParserOutputGenerator( 'en' );
	}

	private function saveItem( $id ) {
		$this->entityStore->saveEntity(
			new Item( new ItemId( $id ) ),
			__METHOD__,
			$this->getTestUser()->getUser()
		);
	}

	private function saveProperty( $id ) {
		$this->entityStore->saveEntity(
			new Property( new PropertyId( $id ), null, 'wikibase-item' ),
			__METHOD__,
			$this->getTestUser()->getUser()
		);
	}

}
