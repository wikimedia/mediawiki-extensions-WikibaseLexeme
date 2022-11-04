<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Rdf;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Presentation\Rdf\LexemeRdfBuilder;
use Wikibase\Lexeme\Presentation\Rdf\LexemeSpecificComponentsRdfBuilder;
use Wikibase\Repo\Rdf\FullStatementRdfBuilder;
use Wikibase\Repo\Rdf\FullStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfProducer;
use Wikibase\Repo\Rdf\TruthyStatementRdfBuilder;
use Wikibase\Repo\Rdf\TruthyStatementRdfBuilderFactory;

/**
 * @covers \Wikibase\Lexeme\Presentation\Rdf\LexemeRdfBuilder
 *
 * @group WikibaseLexeme
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 */
class LexemeRdfBuilderTest extends TestCase {

	private $truthyStatementRdfBuilderFactory;
	private $fullStatementRdfBuilderFactory;
	private $lexemeSpecificComponentsRdfBuilder;

	protected function setUp(): void {
		parent::setUp();
		$this->truthyStatementRdfBuilderFactory = $this->createMock( TruthyStatementRdfBuilderFactory::class );
		$this->fullStatementRdfBuilderFactory = $this->createMock( FullStatementRdfBuilderFactory::class );
		$this->lexemeSpecificComponentsRdfBuilder = $this->createMock( LexemeSpecificComponentsRdfBuilder::class );
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testInternalRdfBuildersCallsAddEntity_dependingOnFlavorFlags(
		int $flavorFlags,
		Lexeme $lexeme,
		bool $expectTruthyBuilderCalled = false,
		bool $expectFullBuilderCalled = false
	): void {
		$this->lexemeSpecificComponentsRdfBuilder->expects( $this->atLeastOnce() )
			->method( 'addEntity' )
			->with( $lexeme );

		$truthyStatementRdfBuilder = $this->createMock( TruthyStatementRdfBuilder::class );
		$this->truthyStatementRdfBuilderFactory->method( 'getTruthyStatementRdfBuilder' )
			->willReturn( $truthyStatementRdfBuilder );

		if ( $expectTruthyBuilderCalled ) {
			$truthyStatementRdfBuilder->expects( $this->atLeastOnce() )->method( 'addEntity' )->with( $lexeme );
		} else {
			$truthyStatementRdfBuilder->expects( $this->never() )->method( 'addEntity' );
		}

		$fullStatementRdfBuilder = $this->createMock( FullStatementRdfBuilder::class );
		$this->fullStatementRdfBuilderFactory->method( 'getFullStatementRdfBuilder' )
			->willReturn( $fullStatementRdfBuilder );

		if ( $expectFullBuilderCalled ) {
			$fullStatementRdfBuilder->expects( $this->atLeastOnce() )->method( 'addEntity' )->with( $lexeme );
		} else {
			$fullStatementRdfBuilder->expects( $this->never() )->method( 'addEntity' );
		}

		$builder = $this->getBuilder( $flavorFlags );
		$builder->addEntity( $lexeme );
	}

	public function provideAddEntity(): array {
		return [
			'No flavors selected' => [ 0, new Lexeme() ],
			'Just truthy statements requested' => [
				RdfProducer::PRODUCE_TRUTHY_STATEMENTS,
				new Lexeme(),
				true
			],
			'Full statements requested' => [
				RdfProducer::PRODUCE_ALL_STATEMENTS,
				new Lexeme(),
				false,
				true
			],
			'All statements requested' => [
				RdfProducer::PRODUCE_ALL,
				new Lexeme(),
				true,
				true
			],
		];
	}

	private function getBuilder( $flavorFlags ): LexemeRdfBuilder {
		return new LexemeRdfBuilder(
			$flavorFlags,
			$this->truthyStatementRdfBuilderFactory,
			$this->fullStatementRdfBuilderFactory,
			$this->lexemeSpecificComponentsRdfBuilder
		);
	}
}
