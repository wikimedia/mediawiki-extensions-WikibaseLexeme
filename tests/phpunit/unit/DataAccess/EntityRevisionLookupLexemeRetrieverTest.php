<?php

declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Lexeme\DataAccess\Store\EntityRevisionLookupLexemeRetriever;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Gloss;
use Wikibase\Lexeme\Domain\Model\ReadModel\Glosses;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemma;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\Sense;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\Statement;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Statements\Domain\Services\StatementReadModelConverter;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\EntityRevisionLookupLexemeRetriever
 *
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupLexemeRetrieverTest extends TestCase {

	public function testGetLexeme(): void {
		$lexemeId = new LexemeId( 'L123' );
		$language = 'en';
		$lemma = 'potato';
		$gloss = 'an edible tuber';
		$lexemeWriteModel = NewLexeme::havingId( $lexemeId )
			->withLemma( $language, $lemma )
			->withSense( NewSense::havingId( 'S1' )->withGloss( $language, $gloss ) )
			->build();
		$expectedLexemeReadModel = new Lexeme(
			$lexemeId,
			new Lemmas( new Lemma( $language, $lemma ) ),
			new StatementList(),
			new Senses(
				new Sense(
					new SenseId( 'L123-S1' ),
					new Glosses( new Gloss( $language, $gloss ) ),
					new StatementList()
				)
			),
		);

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $lexemeId )
			->willReturn( new EntityRevision( $lexemeWriteModel ) );

		$retriever = new EntityRevisionLookupLexemeRetriever(
			$entityRevisionLookup,
			$this->createStub( StatementReadModelConverter::class ),
		);

		$this->assertEquals( $expectedLexemeReadModel, $retriever->getLexeme( $lexemeId ) );
	}

	public function testGetLexemeConvertsStatements(): void {
		$lexemeId = new LexemeId( 'L123' );
		$lexemeWriteModel = NewLexeme::havingId( $lexemeId )
			->withStatement( new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) ) )
			->build();

		$readModelStatement = $this->createStub( Statement::class );
		$statementReadModelConverter = $this->createMock( StatementReadModelConverter::class );
		$statementReadModelConverter->expects( $this->once() )
			->method( 'convert' )
			->willReturn( $readModelStatement );

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $lexemeId )
			->willReturn( new EntityRevision( $lexemeWriteModel ) );

		$retriever = new EntityRevisionLookupLexemeRetriever(
			$entityRevisionLookup,
			$statementReadModelConverter,
		);

		$this->assertEquals(
			new StatementList( $readModelStatement ),
			$retriever->getLexeme( $lexemeId )->statements,
		);
	}

	public function testGetLexemeConvertsSenseStatements(): void {
		$lexemeId = new LexemeId( 'L123' );
		$lexemeWriteModel = NewLexeme::havingId( $lexemeId )
			->withSense(
				NewSense::havingId( 'S1' )->withStatement( new NumericPropertyId( 'P1' ) )
			)
			->build();

		$readModelStatement = $this->createStub( Statement::class );
		$statementReadModelConverter = $this->createMock( StatementReadModelConverter::class );
		$statementReadModelConverter->expects( $this->once() )
			->method( 'convert' )
			->willReturn( $readModelStatement );

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $lexemeId )
			->willReturn( new EntityRevision( $lexemeWriteModel ) );

		$retriever = new EntityRevisionLookupLexemeRetriever(
			$entityRevisionLookup,
			$statementReadModelConverter,
		);

		$senses = $retriever->getLexeme( $lexemeId )->senses;
		$this->assertEquals(
			new StatementList( $readModelStatement ),
			iterator_to_array( $senses, false )[0]->statements,
		);
	}

	public function testGivenLexemeDoesNotExist_getLexemeReturnsNull(): void {
		$lexemeId = new LexemeId( 'L321' );

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $lexemeId )
			->willReturn( null );

		$retriever = new EntityRevisionLookupLexemeRetriever(
			$entityRevisionLookup,
			$this->createStub( StatementReadModelConverter::class ),
		);

		$this->assertNull( $retriever->getLexeme( $lexemeId ) );
	}

	public function testGivenLexemeRedirected_getLexemeReturnsNull(): void {
		$lexemeId = new LexemeId( 'L321' );

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $lexemeId )
			->willThrowException( $this->createStub( RevisionedUnresolvedRedirectException::class ) );

		$retriever = new EntityRevisionLookupLexemeRetriever(
			$entityRevisionLookup,
			$this->createStub( StatementReadModelConverter::class ),
		);

		$this->assertNull( $retriever->getLexeme( $lexemeId ) );
	}

}
