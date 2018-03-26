<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\ChangeOp\Deserialization\FormChangeOpDeserializer;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\Deserialization\FormChangeOpDeserializerTest
 *
 * @license GPL-2.0-or-later
 */
class FormChangeOpDeserializerTest extends TestCase {

	public function testGivenChangeRequestWithRemoveForm_formIsRemoved() {
		$lexeme = $this->getEnglishLexeme( 'L107' );

		$deserializer = new FormChangeOpDeserializer();
		$changeOps = $deserializer->createEntityChangeOp(
			[ 'forms' => [ [ 'id' => 'L107-F1', 'remove' => '' ] ] ]
		);

		$summary = new Summary();
		$changeOps->apply( $lexeme, $summary );

		$this->assertCount( 1, $changeOps->getActions() );
		$this->assertCount( 0, $lexeme->getForms() );
		$this->assertSame( 'remove-form', $summary->getMessageKey() );
		$this->assertSame( [ 'L107-F1' ], $summary->getCommentArgs() );
	}

	public function testGivenChangeRequestWithOneOfTwoRemoveForm_requestedFormIsRemoved() {
		$lexeme = $this->getEnglishLexeme( 'L107' );
		$lexeme->addForm( new TermList( [ new Term( 'en', 'crabapple' ) ] ), [] );

		$deserializer = new FormChangeOpDeserializer();
		$changeOps = $deserializer->createEntityChangeOp(
			[ 'forms' => [ [ 'id' => 'L107-F1', 'remove' => '' ] ] ]
		);

		$summary = new Summary();
		$changeOps->apply( $lexeme, $summary );

		$this->assertCount( 1, $changeOps->getActions() );
		$this->assertCount( 1, $lexeme->getForms() );
		$this->assertInstanceOf( Form::class, $lexeme->getForms()->getById( new FormId( 'L107-F2' ) ) );
		$this->assertSame( 'remove-form', $summary->getMessageKey() );
		$this->assertSame( [ 'L107-F1' ], $summary->getCommentArgs() );
	}

	public function testGivenChangeRequestWithAllFormRemove_formsAreRemoved() {
		$lexeme = $this->getEnglishLexeme( 'L107' );
		$lexeme->addForm( new TermList( [ new Term( 'en', 'crabapple' ) ] ), [] );

		$deserializer = new FormChangeOpDeserializer();
		$changeOps = $deserializer->createEntityChangeOp(
			[ 'forms' => [ [ 'id' => 'L107-F1', 'remove' => '' ], [ 'id' => 'L107-F2', 'remove' => '' ] ] ]
		);

		$summary = new Summary();
		$changeOps->apply( $lexeme, $summary );

		$this->assertCount( 1, $changeOps->getActions() );
		$this->assertCount( 0, $lexeme->getForms() );
		$this->assertSame( 'update', $summary->getMessageKey() );
		$this->assertSame( [], $summary->getCommentArgs() );
	}

	public function testGivenChangeRequestWithoutRemoveForm_formStaysIntact() {
		$lexeme = $this->getEnglishLexeme( 'L107' );

		$deserializer = new FormChangeOpDeserializer();
		$changeOps = $deserializer->createEntityChangeOp(
			[ 'forms' => [ [ 'id' => 'L107-F1' ] ] ]
		);

		$summary = new Summary();
		$changeOps->apply( $lexeme, $summary );

		$this->assertCount( 0, $changeOps->getActions() );
		$this->assertCount( 1, $lexeme->getForms() );
		$this->assertNull( $summary->getMessageKey() );
	}

	/**
	 * @expectedException \Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException
	 * @expectedExceptionMessage Parameter "[forms][0][id]" is required
	 */
	public function testGivenChangeRequestWithoutId_exceptionIsThrown() {
		$lexeme = $this->getEnglishLexeme( 'L107' );

		$deserializer = new FormChangeOpDeserializer();
		$changeOps = $deserializer->createEntityChangeOp(
			[ 'forms' => [ [ 'remove' => '' ] ] ]
		);

		$changeOps->apply( $lexeme );
	}

	/**
	 * @expectedException \Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException
	 * @expectedExceptionMessage Expected argument of type "string", "array" given
	 */
	public function testGivenChangeRequestWithOffTypeId_exceptionIsThrown() {
		$lexeme = $this->getEnglishLexeme( 'L107' );

		$deserializer = new FormChangeOpDeserializer();
		$changeOps = $deserializer->createEntityChangeOp(
			[ 'forms' => [ [ 'id' => [ 'hack' ], 'remove' => '' ] ] ]
		);

		$changeOps->apply( $lexeme );
	}

	private function getEnglishLexeme( $id ) {
		return NewLexeme::havingId( $id )
			->withLemma( 'en', 'apple' )
			->withForm( new Form(
				new FormId(
					$this->formatFormId( $id, 'F1' )
				),
				new TermList( [
					new Term( 'en', 'Malus' )
				] ),
				[]
			) )
			->build();
	}

	private function formatFormId( $lexemeId, $formId ) {
		return $lexemeId . '-' . $formId;
	}

}
