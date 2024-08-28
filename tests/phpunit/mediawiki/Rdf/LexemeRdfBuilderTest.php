<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Rdf;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Presentation\Rdf\LexemeRdfBuilder;
use Wikibase\Repo\Rdf\EntityRdfBuilder;
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

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testInternalRdfBuildersCallsAddEntity_dependingOnFlavorFlags(
		int $flavorFlags,
		bool $expectTruthyBuilderCalled,
		bool $expectFullBuilderCalled
	): void {
		$lexeme = new Lexeme();

		$truthyBuilder = $this->createMock( TruthyStatementRdfBuilder::class );
		$truthyBuilder->expects( $this->exactly( (int)$expectTruthyBuilderCalled ) )
			->method( 'addEntity' )
			->with( $lexeme );
		$truthyBuilderFactory = $this->createMock( TruthyStatementRdfBuilderFactory::class );
		$truthyBuilderFactory->method( 'getTruthyStatementRdfBuilder' )
			->willReturn( $truthyBuilder );

		$fullBuilder = $this->createMock( FullStatementRdfBuilder::class );
		$fullBuilder->expects( $this->exactly( (int)$expectFullBuilderCalled ) )
			->method( 'addEntity' )
			->with( $lexeme );
		$fullBuilderFactory = $this->createMock( FullStatementRdfBuilderFactory::class );
		$fullBuilderFactory->method( 'getFullStatementRdfBuilder' )
			->willReturn( $fullBuilder );

		$lexemeSpecificComponentsRdfBuilder = $this->createMock( EntityRdfBuilder::class );
		$lexemeSpecificComponentsRdfBuilder->expects( $this->once() )
			->method( 'addEntity' )
			->with( $lexeme );

		$builder = new LexemeRdfBuilder(
			$flavorFlags,
			$truthyBuilderFactory,
			$fullBuilderFactory,
			$lexemeSpecificComponentsRdfBuilder
		);
		$builder->addEntity( $lexeme );
	}

	public static function provideAddEntity(): array {
		return [
			'No flavors selected' => [
				0,
				false,
				false,
			],
			'Just truthy statements requested' => [
				RdfProducer::PRODUCE_TRUTHY_STATEMENTS,
				true,
				false,
			],
			'Full statements requested' => [
				RdfProducer::PRODUCE_ALL_STATEMENTS,
				false,
				true,
			],
			'All statements requested' => [
				RdfProducer::PRODUCE_ALL,
				true,
				true,
			],
		];
	}

}
