<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use ApiUsageException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormAdd;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormListChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOps;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormListChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class FormListChangeOpDeserializerTest extends TestCase {

	public function testGivenChangeRequestWithOneOfTwoRemoveForm_requestedFormIsRemoved() {
		$lexeme = $this->getEnglishNewLexeme( 'L107' )
			->withForm(
				NewForm::havingId( 'F2' )
					->andRepresentation( 'en', 'crabapple' )
			)->build();

		$changeOps = $this->getDeserializer()->createEntityChangeOp(
			[ 'forms' => [ [ 'id' => 'L107-F1', 'remove' => '' ] ] ]
		);

		$summary = new Summary( 'wbeditentity' );
		$changeOps->apply( $lexeme, $summary );

		$this->assertCount( 1, $changeOps->getActions() );
		$this->assertCount( 1, $lexeme->getForms() );
		$this->assertInstanceOf( Form::class, $lexeme->getForms()->getById( new FormId( 'L107-F2' ) ) );

		$this->assertSame(
			'wbeditentity-update',
			$summary->getMessageKey(),
			'Propagation of atomic summary not implemented, yet.'
		);
		$this->assertSame( [], $summary->getCommentArgs() );
	}

	public function testGivenChangeRequestWithAllFormRemove_formsAreRemoved() {
		$lexeme = $this->getEnglishNewLexeme( 'L107' )
			->withForm(
				NewForm::havingId( 'F2' )
					->andRepresentation( 'en', 'crabapple' )
			)
			->build();

		$changeOps = $this->getDeserializer()->createEntityChangeOp(
			[ 'forms' => [ [ 'id' => 'L107-F1', 'remove' => '' ], [ 'id' => 'L107-F2', 'remove' => '' ] ] ]
		);

		$summary = new Summary( 'wbeditentity' );
		$changeOps->apply( $lexeme, $summary );

		$this->assertCount( 1, $changeOps->getActions() );
		$this->assertCount( 0, $lexeme->getForms() );
		$this->assertSame(
			'wbeditentity-update',
			$summary->getMessageKey(),
			'Proper aggregation not implemented, yet.'
		);
		$this->assertSame( [], $summary->getCommentArgs() );
	}

	public function testGivenChangeRequestWithoutRemoveForm_formStaysIntact() {
		$lexeme = $this->getEnglishNewLexeme( 'L107' )->build();

		$changeOps = $this->getDeserializer()->createEntityChangeOp(
			[ 'forms' => [ [ 'id' => 'L107-F1' ] ] ]
		);

		$summary = new Summary( 'wbeditentity' );
		$changeOps->apply( $lexeme, $summary );

		$this->assertCount( 0, $changeOps->getActions() );
		$this->assertCount( 1, $lexeme->getForms() );
		$this->assertSame(
			'wbeditentity-update',
			$summary->getMessageKey(),
			'ChangeOps::apply considers change happened as no recursive count done'
		);
	}

	public function testGivenChangeRequestWithOneFormAdd_addOpIsUsed() {
		$changeOps = $this->getDeserializer()->createEntityChangeOp( [
			'forms' => [
				[
					'add' => '',
					'representations' => [ 'de' => [ 'language' => 'de', 'value' => 'term' ] ]
				]
			]
		] );

		$this->assertCount( 2, $changeOps->getChangeOps() );
		$lexemeChangeOps = $changeOps->getChangeOps()[0];
		$this->assertCount( 1, $lexemeChangeOps->getChangeOps() );
		$this->assertInstanceOf( ChangeOpFormAdd::class, $lexemeChangeOps->getChangeOps()[0] );
	}

	public function testGivenChangeRequestWithoutId_exceptionIsThrown() {
		$lexeme = $this->getEnglishNewLexeme( 'L107' )->build();

		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage( 'Field "id" at "forms/0" in parameter "data" is required' );
		$changeOps = $this->getDeserializer()->createEntityChangeOp(
			[ 'forms' => [ [ 'remove' => '' ] ] ]
		);

		$changeOps->apply( $lexeme );
	}

	public function testGivenNonArrayForms_exceptionIsThrown(): void {
		$lexeme = $this->getEnglishNewLexeme( 'L107' )->build();

		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage(
			'Field "forms" in parameter "data" expected to be of type "array". Given: "NULL"' );
		$changeOps = $this->getDeserializer()->createEntityChangeOp(
			[ 'forms' => null ]
		);

		$changeOps->apply( $lexeme );
	}

	public function testGivenNonArrayFormSerialization_exceptionIsThrown(): void {
		$lexeme = $this->getEnglishNewLexeme( 'L107' )->build();

		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage(
			'Field "forms/0" in parameter "data" expected to be of type "array". Given: "NULL"' );
		$changeOps = $this->getDeserializer()->createEntityChangeOp(
			[ 'forms' => [ null ] ]
		);

		$changeOps->apply( $lexeme );
	}

	private function getDeserializer() {
		$formIdDeserializer = $this->createMock( FormIdDeserializer::class );
		$formIdDeserializer->method( 'deserialize' )
			->willReturnCallback( static function ( $formId ) {
				return new FormId( $formId );
			} );

		$formChangeOpDeserializer = $this->createMock( FormChangeOpDeserializer::class );
		$formChangeOpDeserializer->method( 'createEntityChangeOp' )
			->willReturn( new ChangeOps() );

		$deserializer = new FormListChangeOpDeserializer(
			$formIdDeserializer,
			$formChangeOpDeserializer
		);

		$deserializer->setContext( ValidationContext::create( 'data' )->at( 'forms' ) );

		return $deserializer;
	}

	private function getEnglishNewLexeme( $id ) {
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
			) );
	}

	private function formatFormId( $lexemeId, $formId ) {
		return $lexemeId . '-' . $formId;
	}

}
