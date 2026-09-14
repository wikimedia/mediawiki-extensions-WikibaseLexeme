<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\DataAccess\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement as StatementWriteModel;
use Wikibase\Lexeme\DataAccess\Store\LexemeReadModelConverter;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Form;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\Gloss;
use Wikibase\Lexeme\Domain\Model\ReadModel\Glosses;
use Wikibase\Lexeme\Domain\Model\ReadModel\GrammaticalFeatures;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemma;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\Representation;
use Wikibase\Lexeme\Domain\Model\ReadModel\Representations;
use Wikibase\Lexeme\Domain\Model\ReadModel\Sense;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\Statement;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Statements\Domain\Services\StatementReadModelConverter;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\LexemeReadModelConverter
 *
 * @license GPL-2.0-or-later
 */
class LexemeReadModelConverterTest extends TestCase {

	public function testConvert(): void {
		$lexemeId = new LexemeId( 'L123' );
		$languageCode = 'en';
		$lemma = 'potato';
		$lexicalCategory = new ItemId( 'Q1' );
		$language = new ItemId( 'Q2' );
		$representation = 'potatoes';
		$grammaticalFeatures = [ new ItemId( 'Q3' ), new ItemId( 'Q4' ) ];
		$gloss = 'an edible tuber';
		$lexemeWriteModel = NewLexeme::havingId( $lexemeId )
			->withLemma( $languageCode, $lemma )
			->withLexicalCategory( $lexicalCategory )
			->withLanguage( $language )
			->withForm( NewForm::havingId( 'F1' )
				->andRepresentation( $languageCode, $representation )
				->andGrammaticalFeature( $grammaticalFeatures[0] )
				->andGrammaticalFeature( $grammaticalFeatures[1] )
			)
			->withSense( NewSense::havingId( 'S1' )->withGloss( $languageCode, $gloss ) )
			->build();

		$this->assertEquals(
			new Lexeme(
				$lexemeId,
				new Lemmas( new Lemma( $languageCode, $lemma ) ),
				$lexicalCategory,
				$language,
				new StatementList(),
				new Forms(
					new Form(
						new FormId( 'L123-F1' ),
						new Representations( new Representation( $languageCode, $representation ) ),
						new GrammaticalFeatures( ...$grammaticalFeatures ),
						new StatementList()
					)
				),
				new Senses(
					new Sense(
						new SenseId( 'L123-S1' ),
						new Glosses( new Gloss( $languageCode, $gloss ) ),
						new StatementList()
					)
				),
			),
			$this->newLexemeConverter( $this->createStub( StatementReadModelConverter::class ) )
				->convert( $lexemeWriteModel )
		);
	}

	public function testConvertsLexemeStatements(): void {
		$lexemeWriteModel = NewLexeme::havingId( 'L123' )
			->withStatement( new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) ) )
			->build();
		$statement = $lexemeWriteModel->getStatements()->toArray()[0];
		$readModelStatement = $this->createStub( Statement::class );

		$this->assertEquals(
			new StatementList( $readModelStatement ),
			$this->newLexemeConverter( $this->mockStatementConverter( $statement, $readModelStatement ) )
				->convert( $lexemeWriteModel )->statements
		);
	}

	public function testConvertsFormStatements(): void {
		$lexemeWriteModel = NewLexeme::havingId( 'L123' )
			->withForm( NewForm::havingId( 'F1' )
				->andStatement( new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) ) ) )
			->build();
		$statement = $lexemeWriteModel->getForms()->toArray()[0]->getStatements()->toArray()[0];
		$readModelStatement = $this->createStub( Statement::class );

		$forms = $this->newLexemeConverter( $this->mockStatementConverter( $statement, $readModelStatement ) )
			->convert( $lexemeWriteModel )->forms;

		$this->assertEquals(
			new StatementList( $readModelStatement ),
			iterator_to_array( $forms, false )[0]->statements,
		);
	}

	public function testConvertsSenseStatements(): void {
		$lexemeWriteModel = NewLexeme::havingId( 'L123' )
			->withSense( NewSense::havingId( 'S1' )
				->withStatement( new NumericPropertyId( 'P1' ) ) )
			->build();
		$statement = $lexemeWriteModel->getSenses()->toArray()[0]->getStatements()->toArray()[0];
		$readModelStatement = $this->createStub( Statement::class );

		$senses = $this->newLexemeConverter( $this->mockStatementConverter( $statement, $readModelStatement ) )
			->convert( $lexemeWriteModel )->senses;

		$this->assertEquals(
			new StatementList( $readModelStatement ),
			iterator_to_array( $senses, false )[0]->statements,
		);
	}

	private function newLexemeConverter( StatementReadModelConverter $statementConverter ): LexemeReadModelConverter {
		return new LexemeReadModelConverter( $statementConverter );
	}

	private function mockStatementConverter(
		StatementWriteModel $writeModelStatement,
		Statement $readModelStatement
	): StatementReadModelConverter {
		$statementReadModelConverter = $this->createMock( StatementReadModelConverter::class );
		$statementReadModelConverter->expects( $this->once() )
			->method( 'convert' )
			->with( $writeModelStatement )
			->willReturn( $readModelStatement );

		return $statementReadModelConverter;
	}

}
