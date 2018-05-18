<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lexeme\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\FormChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\FormListChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LanguageChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LemmaChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LexemeChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LexicalCategoryChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeIntegrationTestCase;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\Deserialization\LexemeChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class LexemeChangeOpDeserializerTest extends WikibaseLexemeIntegrationTestCase {

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

		$lexemeChangeOpDeserializer = new LexemeChangeOpDeserializer(
			new LemmaChangeOpDeserializer(
				new LexemeTermSerializationValidator(
					new LexemeTermLanguageValidator( new StaticContentLanguages( [ 'en', 'enm' ] ) )
				),
				$lexemeValidatorFactory,
				$stringNormalizer
			),
			new LexicalCategoryChangeOpDeserializer( $lexemeValidatorFactory, $stringNormalizer ),
			new LanguageChangeOpDeserializer( $lexemeValidatorFactory, $stringNormalizer ),
			new ClaimsChangeOpDeserializer(
				WikibaseRepo::getDefaultInstance()->getExternalFormatStatementDeserializer(),
				WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()->getStatementChangeOpFactory()
			),
			new FormListChangeOpDeserializer(
				new FormIdDeserializer( WikibaseRepo::getDefaultInstance()->getEntityIdParser() ),
				new FormChangeOpDeserializer(
					WikibaseRepo::getDefaultInstance()->getEntityLookup(),
					WikibaseRepo::getDefaultInstance()->getEntityIdParser(),
					new EditFormChangeOpDeserializer(
						new RepresentationsChangeOpDeserializer(
							new TermDeserializer(),
							new LexemeTermSerializationValidator(
								new LexemeTermLanguageValidator( new StaticContentLanguages( [ 'en', 'de' ] ) )
							)
						),
						new ItemIdListDeserializer( new ItemIdParser() )
					)
				)
			)
		);

		$lexemeChangeOpDeserializer->setContext( ValidationContext::create( 'data' ) );

		return $lexemeChangeOpDeserializer;
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

	public function testGivenChangeRequestWithEmptyLemma_exceptionIsThrown() {
		$deserializer = $this->getChangeOpDeserializer();

		try {
			$deserializer->createEntityChangeOp(
				[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => '' ] ] ]
			);
		} catch ( \ApiUsageException $ex ) {
			$exception = $ex;
		}

		$message = $exception->getMessageObject();
		$this->assertEquals( 'unprocessable-request', $message->getApiCode() );
		$this->assertEquals(
			'wikibaselexeme-api-error-lexeme-term-text-cannot-be-empty',
			$message->getKey()
		);
		$this->assertEquals(
			[ 'parameterName' => 'lemmas', 'fieldPath' => [ 'en' ] ],
			$message->getApiData()
		);
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

	public function testRemoveExistingForms_formsAreRemoved() {
		$lexeme = $this->getEnglishLexeme();
		$lexeme->addForm( new TermList( [ new Term( 'en', 'apple' ) ] ), [] );
		$lexeme->addForm( new TermList( [ new Term( 'en', 'Maluse' ) ] ), [] );

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp( [
			'forms' => [
				[ 'id' => 'L500-F1', 'remove' => '' ],
				[ 'id' => 'L500-F2', 'remove' => '' ]
			]
		] );

		$changeOp->apply( $lexeme );

		$this->assertCount( 0, $lexeme->getForms() );
	}

	public function testRemoveOneOfTwoExistingForms_formIsRemovedOtherRemains() {
		$lexeme = $this->getEnglishLexeme();
		$lexeme->addForm( new TermList( [ new Term( 'en', 'apple' ) ] ), [] );
		$persistentTerm = new Term( 'en', 'Malus' );
		$lexeme->addForm( new TermList( [ $persistentTerm ] ), [] );

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp( [
			'forms' => [
				[ 'id' => 'L500-F1', 'remove' => '' ]
			]
		] );

		$changeOp->apply( $lexeme );

		$this->assertCount( 1, $lexeme->getForms() );
		$this->assertTrue(
			$lexeme->getForm( new FormId( 'L500-F2' ) )->getRepresentations()->hasTerm( $persistentTerm )
		);
	}

}
