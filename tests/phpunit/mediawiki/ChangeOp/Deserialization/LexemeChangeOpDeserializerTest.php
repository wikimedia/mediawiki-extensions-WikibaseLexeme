<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lexeme\ChangeOp\Deserialization\LanguageChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LemmaChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LexemeChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LexicalCategoryChangeOpDeserializer;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\Deserialization\LexemeChangeOpDeserializer
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class LexemeChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	private function getLexemeValidatorFactory() {
		$duplicateDetector = $this->getMockBuilder( LabelDescriptionDuplicateDetector::class )
			->disableOriginalConstructor()
			->getMock();

		return new LexemeValidatorFactory(
			100,
			new TermValidatorFactory(
				100,
				[ 'en', 'enm' ],
				new ItemIdParser(),
				$duplicateDetector
			),
			[]
		);
	}

	private function getChangeOpDeserializer() {
		$lexemeValidatorFactory = $this->getLexemeValidatorFactory();
		$stringNormalizer = new StringNormalizer();

		return new LexemeChangeOpDeserializer(
			new LemmaChangeOpDeserializer(
				new TermChangeOpSerializationValidator( new StaticContentLanguages( [ 'en', 'enm' ] ) ),
				$lexemeValidatorFactory,
				$stringNormalizer
			),
			new LexicalCategoryChangeOpDeserializer( $lexemeValidatorFactory, $stringNormalizer ),
			new LanguageChangeOpDeserializer( $lexemeValidatorFactory, $stringNormalizer ),
			new ClaimsChangeOpDeserializer(
				WikibaseRepo::getDefaultInstance()->getExternalFormatStatementDeserializer(),
				WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()->getStatementChangeOpFactory()
			)
		);
	}

	private function getEnglishLexeme() {
		return new Lexeme(
			new LexemeId( 'L500' ),
			new TermList( [ new Term( 'en', 'apple' ) ] ),
			new ItemId( 'Q1084' ),
			new ItemId( 'Q1860' )
		);
	}

	public function testGivenChangeRequestWithLemma_lemmaIsSet() {
		$lexeme = $this->getEnglishLexeme();

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'worm' ] ] ]
		);

		$changeOp->apply( $lexeme );

		$this->assertSame( 'worm', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithLemmaAndNewLanguageCode_lemmaIsAdded() {
		$lexeme = $this->getEnglishLexeme();

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'enm' => [ 'language' => 'enm', 'value' => 'appel' ] ] ]
		);

		$changeOp->apply( $lexeme );

		$this->assertTrue( $lexeme->getLemmas()->hasTermForLanguage( 'en' ) );
		$this->assertSame( 'appel', $lexeme->getLemmas()->getByLanguage( 'enm' )->getText() );
	}

	public function testGivenChangeRequestWithRemoveLemma_lemmaIsRemoved() {
		$lexeme = $this->getEnglishLexeme();

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ]
		);

		$changeOp->apply( $lexeme );

		$this->assertFalse( $lexeme->getLemmas()->hasTermForLanguage( 'en' ) );
	}

	public function testGivenChangeRequestWithEmptyLemma_lemmaIsRemoved() {
		$lexeme = $this->getEnglishLexeme();

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => '' ] ] ]
		);

		$changeOp->apply( $lexeme );

		$this->assertFalse( $lexeme->getLemmas()->hasTermForLanguage( 'en' ) );
	}

	public function testGivenChangeRequestWithLanguage_languageIsChanged() {
		$lexeme = $this->getEnglishLexeme();

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp( [ 'language' => 'Q123' ] );

		$changeOp->apply( $lexeme );

		$this->assertEquals( 'Q123', $lexeme->getLanguage()->getSerialization() );
	}

	public function testGivenChangeRequestWithLexicalCategory_lexicalCategoryIsChanged() {
		$lexeme = $this->getEnglishLexeme();

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp( [ 'lexicalCategory' => 'Q300' ] );

		$changeOp->apply( $lexeme );

		$this->assertSame( 'Q300', $lexeme->getLexicalCategory()->getSerialization() );
	}

	public function testGivenChangeRequestWithEmptyLanguage_exceptionIsThrown() {
		$deserializer = $this->getChangeOpDeserializer();

		$exception = null;

		try {
			$deserializer->createEntityChangeOp( [ 'language' => '' ] );
		} catch ( ChangeOpDeserializationException $ex ) {
			$exception = $ex;
		}

		$this->assertInstanceOf( ChangeOpDeserializationException::class, $exception );
		$this->assertEquals( 'invalid-item-id', $exception->getErrorCode() );
	}

	public function testGivenChangeRequestWithEmptyLexicalCategory_exceptionIsThrown() {
		$deserializer = $this->getChangeOpDeserializer();

		$exception = null;

		try {
			$deserializer->createEntityChangeOp( [ 'lexicalCategory' => '' ] );
		} catch ( ChangeOpDeserializationException $ex ) {
			$exception = $ex;
		}

		$this->assertInstanceOf( ChangeOpDeserializationException::class, $exception );
		$this->assertEquals( 'invalid-item-id', $exception->getErrorCode() );
	}

	public function testGivenChangeRequestWithManyFields_allFieldsAreUpdated() {
		$lexeme = $this->getEnglishLexeme();

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp( [
			'language' => 'Q123',
			'lexicalCategory' => 'Q321',
			'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'worm' ] ]
		] );

		$changeOp->apply( $lexeme );

		$this->assertEquals( 'Q123', $lexeme->getLanguage()->getSerialization() );
		$this->assertEquals( 'Q321', $lexeme->getLexicalCategory()->getSerialization() );
		$this->assertEquals( 'worm', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithStatement_statementIsAdded() {
		$lexeme = $this->getEnglishLexeme();

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp( [ 'claims' => [
			[
				'mainsnak' => [ 'snaktype' => 'novalue', 'property' => 'P1' ],
				'type' => 'statement',
				'rank' => 'normal'
			]
		] ] );

		$changeOp->apply( $lexeme );

		$this->assertCount( 1, $lexeme->getStatements()->toArray() );
		$this->assertSame(
			'P1',
			$lexeme->getStatements()->getMainSnaks()[0]->getPropertyId()->getSerialization()
		);
	}

	public function testGivenChangeRequestWithStatementRemove_statementIsRemoved() {
		$lexeme = $this->getEnglishLexeme();

		$statement = new Statement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) );
		$statement->setGuid( 'testguid' );

		$lexeme->getStatements()->addNewStatement(
			new PropertyNoValueSnak( new PropertyId( 'P2' ) ),
			null,
			null,
			'testguid'
		);

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp(
			[ 'claims' => [ [ 'remove' => '', 'id' => 'testguid' ] ] ]
		);

		$changeOp->apply( $lexeme );

		$this->assertTrue( $lexeme->getStatements()->isEmpty() );
	}

	public function testNonLexemeRelatedFieldsAreIgnored() {
		$lexeme = $this->getEnglishLexeme();

		$englishLemma = $lexeme->getLemmas()->getByLanguage( 'en' )->getText();
		$language = $lexeme->getLanguage()->getSerialization();
		$lexicalCategory = $lexeme->getLexicalCategory()->getSerialization();

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp(
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'pear' ] ] ]
		);

		$changeOp->apply( $lexeme );

		$this->assertSame( 'apple', $englishLemma );
		$this->assertSame( 'Q1860', $language );
		$this->assertSame( 'Q1084', $lexicalCategory );
	}

}
