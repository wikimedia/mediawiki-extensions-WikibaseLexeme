<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormListChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LanguageChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LemmaChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LexemeChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LexicalCategoryChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseListChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeIntegrationTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LexemeChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class LexemeChangeOpDeserializerTest extends WikibaseLexemeIntegrationTestCase {

	private function getChangeOpDeserializer() {
		$stringNormalizer = new StringNormalizer();
		$statementChangeOpDeserializer = new ClaimsChangeOpDeserializer(
			WikibaseRepo::getExternalFormatStatementDeserializer(),
			WikibaseRepo::getChangeOpFactoryProvider()
				->getStatementChangeOpFactory()
		);
		$entityIdParser = WikibaseRepo::getEntityIdParser();
		$lexemeChangeOpDeserializer = new LexemeChangeOpDeserializer(
			new LemmaChangeOpDeserializer(
				new LexemeTermSerializationValidator(
					new LexemeTermLanguageValidator( new StaticContentLanguages( [ 'en', 'enm' ] ) )
				),
				new LemmaTermValidator( 100 ),
				$stringNormalizer
			),
			new LexicalCategoryChangeOpDeserializer( new CompositeValidator( [] ), $stringNormalizer ),
			new LanguageChangeOpDeserializer( new CompositeValidator( [] ), $stringNormalizer ),
			$statementChangeOpDeserializer,
			new FormListChangeOpDeserializer(
				new FormIdDeserializer( $entityIdParser ),
				new FormChangeOpDeserializer(
					WikibaseRepo::getEntityLookup(),
					$entityIdParser,
					new EditFormChangeOpDeserializer(
						new RepresentationsChangeOpDeserializer(
							new TermDeserializer(),
							$stringNormalizer,
							new LexemeTermSerializationValidator(
								new LexemeTermLanguageValidator( new StaticContentLanguages( [ 'en', 'de' ] ) )
							)
						),
						new ItemIdListDeserializer( new ItemIdParser() ),
						$statementChangeOpDeserializer,
						new CompositeValidator( [] )
					)
				)
			),
			new SenseListChangeOpDeserializer(
				new SenseIdDeserializer( $entityIdParser ),
				new SenseChangeOpDeserializer(
					WikibaseRepo::getEntityLookup(),
					$entityIdParser,
					new EditSenseChangeOpDeserializer(
						new GlossesChangeOpDeserializer(
							new TermDeserializer(),
							$stringNormalizer,
							new LexemeTermSerializationValidator(
								new LexemeTermLanguageValidator( new StaticContentLanguages( [ 'en', 'de' ] ) )
							)
						),
						$statementChangeOpDeserializer
					)
				)
			)
		);

		$lexemeChangeOpDeserializer->setContext( ValidationContext::create( 'data' ) );

		return $lexemeChangeOpDeserializer;
	}

	private function getEnglishNewLexeme() {
		return NewLexeme::havingId( 'L500' )
			->withLemma( 'en', 'apple' )
			->withLexicalCategory( 'Q1084' )
			->withLanguage( 'Q1860' );
	}

	public function testGivenChangeRequestWithLemma_lemmaIsSet() {
		$lexeme = $this->getEnglishNewLexeme()->build();

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'worm' ] ] ]
		);

		$changeOp->apply( $lexeme );

		$this->assertSame( 'worm', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithLemmaAndNewLanguageCode_lemmaIsAdded() {
		$lexeme = $this->getEnglishNewLexeme()->build();

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'enm' => [ 'language' => 'enm', 'value' => 'appel' ] ] ]
		);

		$changeOp->apply( $lexeme );

		$this->assertTrue( $lexeme->getLemmas()->hasTermForLanguage( 'en' ) );
		$this->assertSame( 'appel', $lexeme->getLemmas()->getByLanguage( 'enm' )->getText() );
	}

	public function testGivenChangeRequestWithRemoveLemma_lemmaIsRemoved() {
		$lexeme = $this->getEnglishNewLexeme()->build();

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
			'apierror-wikibaselexeme-lexeme-term-text-cannot-be-empty',
			$message->getKey()
		);
		$this->assertEquals(
			[ 'parameterName' => 'lemmas', 'fieldPath' => [ 'en' ] ],
			$message->getApiData()
		);
	}

	public function testGivenChangeRequestWithLanguage_languageIsChanged() {
		$lexeme = $this->getEnglishNewLexeme()->build();

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp( [ 'language' => 'Q123' ] );

		$changeOp->apply( $lexeme );

		$this->assertEquals( 'Q123', $lexeme->getLanguage()->getSerialization() );
	}

	public function testGivenChangeRequestWithLexicalCategory_lexicalCategoryIsChanged() {
		$lexeme = $this->getEnglishNewLexeme()->build();

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
		$lexeme = $this->getEnglishNewLexeme()->build();

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
		$lexeme = $this->getEnglishNewLexeme()->build();

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
		$lexeme = $this->getEnglishNewLexeme()->build();

		$statement = new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P2' ) ) );
		$statement->setGuid( 'testguid' );

		$lexeme->getStatements()->addNewStatement(
			new PropertyNoValueSnak( new NumericPropertyId( 'P2' ) ),
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
		$lexeme = $this->getEnglishNewLexeme()->build();

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
		$lexeme = $this->getEnglishNewLexeme()
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'apple' )
			)
			->withForm(
				NewForm::havingId( 'F2' )
					->andRepresentation( 'en', 'Maluse' )
			)
			->build();

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
		$lexeme = $this->getEnglishNewLexeme()
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'apple' )
			)
			->withForm(
				NewForm::havingId( 'F2' )
					->andRepresentation( 'en', 'Malus' )
			)
			->build();

		$deserializer = $this->getChangeOpDeserializer();
		$changeOp = $deserializer->createEntityChangeOp( [
			'forms' => [
				[ 'id' => 'L500-F1', 'remove' => '' ]
			]
		] );

		$changeOp->apply( $lexeme );

		$this->assertCount( 1, $lexeme->getForms() );
		$this->assertTrue(
			$lexeme->getForm( new FormId( 'L500-F2' ) )->getRepresentations()->hasTermForLanguage( 'en' )
		);
	}

}
