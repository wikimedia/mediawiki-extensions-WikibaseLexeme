<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use ApiUsageException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseAdd;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseListChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOps;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseListChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class SenseListChangeOpDeserializerTest extends TestCase {

	public function testGivenChangeRequestWithOneOfTwoRemoveSense_requestedSenseIsRemoved() {
		$lexeme = $this->getEnglishNewLexeme( 'L107' )
			->withSense(
				NewSense::havingId( 'S2' )
					->withGloss( 'en', 'crabapple' )
			)->build();

		$changeOps = $this->getDeserializer()->createEntityChangeOp(
			[ 'senses' => [ [ 'id' => 'L107-S1', 'remove' => '' ] ] ]
		);

		$summary = new Summary( 'wbeditentity' );
		$changeOps->apply( $lexeme, $summary );

		$this->assertCount( 1, $changeOps->getActions() );
		$this->assertCount( 1, $lexeme->getSenses() );
		$this->assertInstanceOf(
			Sense::class,
			$lexeme->getSenses()->getById( new SenseId( 'L107-S2' ) )
		);

		$this->assertSame(
			'wbeditentity-update',
			$summary->getMessageKey(),
			'Propagation of atomic summary not implemented, yet.'
		);
		$this->assertSame( [], $summary->getCommentArgs() );
	}

	public function testGivenChangeRequestWithAllSenseRemove_sensesAreRemoved() {
		$lexeme = $this->getEnglishNewLexeme( 'L107' )
			->withSense(
				NewSense::havingId( 'S2' )
					->withGloss( 'en', 'crabapple' )
			)
			->build();

		$changeOps = $this->getDeserializer()->createEntityChangeOp(
			[ 'senses' => [ [ 'id' => 'L107-S1', 'remove' => '' ], [ 'id' => 'L107-S2', 'remove' => '' ] ] ]
		);

		$summary = new Summary( 'wbeditentity' );
		$changeOps->apply( $lexeme, $summary );

		$this->assertCount( 1, $changeOps->getActions() );
		$this->assertCount( 0, $lexeme->getSenses() );
		$this->assertSame(
			'wbeditentity-update',
			$summary->getMessageKey(),
			'Proper aggregation not implemented, yet.'
		);
		$this->assertSame( [], $summary->getCommentArgs() );
	}

	public function testGivenChangeRequestWithoutRemoveSense_senseStaysIntact() {
		$lexeme = $this->getEnglishNewLexeme( 'L107' )->build();

		$changeOps = $this->getDeserializer()->createEntityChangeOp(
			[ 'senses' => [ [ 'id' => 'L107-S1' ] ] ]
		);

		$summary = new Summary( 'wbeditentity' );
		$changeOps->apply( $lexeme, $summary );

		$this->assertCount( 0, $changeOps->getActions() );
		$this->assertCount( 1, $lexeme->getSenses() );
		$this->assertSame(
			'wbeditentity-update',
			$summary->getMessageKey(),
			'ChangeOps::apply considers change happened as no recursive count done'
		);
	}

	public function testGivenChangeRequestWithOneSenseAdd_addOpIsUsed() {
		$changeOps = $this->getDeserializer()->createEntityChangeOp( [
			'senses' => [
				[
					'add' => '',
					'glosses' => [ 'de' => [ 'language' => 'de', 'value' => 'term' ] ]
				]
			]
		] );

		$this->assertCount( 2, $changeOps->getChangeOps() );
		$lexemeChangeOps = $changeOps->getChangeOps()[0];
		$this->assertCount( 1, $lexemeChangeOps->getChangeOps() );
		$this->assertInstanceOf( ChangeOpSenseAdd::class, $lexemeChangeOps->getChangeOps()[0] );
	}

	public function testGivenChangeRequestWithoutId_exceptionIsThrown() {
		$lexeme = $this->getEnglishNewLexeme( 'L107' )->build();

		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage( 'Field "id" at "senses/0" in parameter "data" is required' );
		$changeOps = $this->getDeserializer()->createEntityChangeOp(
			[ 'senses' => [ [ 'remove' => '' ] ] ]
		);

		$changeOps->apply( $lexeme );
	}

	public function testGivenNonArraySenses_exceptionIsThrown(): void {
		$lexeme = $this->getEnglishNewLexeme( 'L107' )->build();

		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage(
			'Field "senses" in parameter "data" expected to be of type "array". Given: "NULL"' );
		$changeOps = $this->getDeserializer()->createEntityChangeOp(
			[ 'senses' => null ]
		);

		$changeOps->apply( $lexeme );
	}

	public function testGivenNonArraySenseSerialization_exceptionIsThrown(): void {
		$lexeme = $this->getEnglishNewLexeme( 'L107' )->build();

		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage(
			'Field "senses/0" in parameter "data" expected to be of type "array". Given: "NULL"' );
		$changeOps = $this->getDeserializer()->createEntityChangeOp(
			[ 'senses' => [ null ] ]
		);

		$changeOps->apply( $lexeme );
	}

	private function getDeserializer() {
		$senseIdDeserializer = $this->createMock( SenseIdDeserializer::class );
		$senseIdDeserializer->method( 'deserialize' )
			->willReturnCallback( static function ( $senseId ) {
				return new SenseId( $senseId );
			} );

		$senseChangeOpDeserializer = $this->createMock( SenseChangeOpDeserializer::class );
		$senseChangeOpDeserializer->method( 'createEntityChangeOp' )
			->willReturn( new ChangeOps() );

		$deserializer = new SenseListChangeOpDeserializer(
			$senseIdDeserializer,
			$senseChangeOpDeserializer
		);

		$deserializer->setContext( ValidationContext::create( 'data' )->at( 'senses' ) );

		return $deserializer;
	}

	private function getEnglishNewLexeme( $id ) {
		return NewLexeme::havingId( $id )
			->withLemma( 'en', 'apple' )
			->withSense( new Sense(
				new SenseId(
					$this->formatSenseId( $id, 'S1' )
				),
				new TermList( [
					new Term( 'en', 'Malus' )
				] )
			) );
	}

	private function formatSenseId( $lexemeId, $senseId ) {
		return $lexemeId . '-' . $senseId;
	}

}
