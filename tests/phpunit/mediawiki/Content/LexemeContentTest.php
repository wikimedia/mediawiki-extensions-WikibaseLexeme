<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Content;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use Diff\DiffOp\Diff\Diff;
use InvalidArgumentException;
use MediaWikiLangTestCase;
use Title;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Diff\LexemeDiff;
use Wikibase\Lexeme\Domain\Diff\LexemeDiffer;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\FormSet;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Domain\Model\SenseSet;
use Wikibase\Lexeme\MediaWiki\Content\LexemeContent;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Content\EntityInstanceHolder;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Content\LexemeContent
 *
 * @license GPL-2.0-or-later
 */
class LexemeContentTest extends MediaWikiLangTestCase {

	/**
	 * @dataProvider invalidConstructorArgsProvider
	 */
	public function testGivenIncorrectConstructorArgs_throwsException(
		$lexemeHolder,
		$redirect,
		$redirectTitle
	) {
		$this->expectException( InvalidArgumentException::class );
		new LexemeContent( $lexemeHolder, $redirect, $redirectTitle );
	}

	public function invalidConstructorArgsProvider() {
		yield 'must not contain lexeme and be a redirect at once' => [
			new EntityInstanceHolder( new Lexeme() ),
			new EntityRedirect( new LexemeId( 'L123' ), new LexemeId( 'L321' ) ),
			null
		];

		yield 'EntityInstanceHolder must contain lexeme' => [
			new EntityInstanceHolder( new Item() ),
			null,
			null
		];

		yield 'when it\'s a redirect, it must contain a redirect title' => [
			null,
			new EntityRedirect( new LexemeId( 'L123' ), new LexemeId( 'L321' ) ),
			null
		];
	}

	public function testGetEntity() {
		$lexeme = new Lexeme( new LexemeId( 'L1' ) );
		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );

		$this->assertSame( $lexeme, $lexemeContent->getEntity() );
	}

	public function testGetEntityRedirect() {
		$redirect = new EntityRedirect(
			new LexemeId( 'L123' ),
			new LexemeId( 'L321' )
		);
		$content = new LexemeContent(
			null,
			$redirect,
			$this->createMock( Title::class )
		);

		$this->assertSame( $redirect, $content->getEntityRedirect() );
	}

	public function testGetRedirectTarget() {
		$redirectTarget = Title::newFromText( 'Lexeme:L123' );
		$redirect = $this->createMock( EntityRedirect::class );
		$content = new LexemeContent( null, $redirect, $redirectTarget );

		$this->assertSame( $redirectTarget, $content->getRedirectTarget() );
	}

	/**
	 * @dataProvider countableLexemeProvider
	 */
	public function testIsCountable( $lexeme ) {
		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );
		$this->assertTrue( $lexemeContent->isCountable() );
	}

	public function countableLexemeProvider() {
		$countable = [];

		$lexeme = new Lexeme( new LexemeId( 'L1' ) );
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );
		$countable[] = [ $lexeme ];

		return $countable;
	}

	public function testNotCountable() {
		$lexemeContent = new LexemeContent( new EntityInstanceHolder(
			new Lexeme( new LexemeId( 'L1' ) )
		) );
		$this->assertFalse( $lexemeContent->isCountable() );
	}

	public function testIsValid() {
		$lexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'test' ) ] ),
			new ItemId( 'Q120' ),
			new ItemId( 'Q121' )
		);

		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );
		$this->assertTrue( $lexemeContent->isValid() );
	}

	public function testGivenRedirect_isValidReturnsTrue() {
		$content = new LexemeContent(
			null,
			$this->createMock( EntityRedirect::class ),
			$this->createMock( Title::class )
		);
		$this->assertTrue( $content->isValid() );
	}

	/**
	 * @dataProvider provideInvalidLexeme
	 */
	public function testNotValid( $lexeme ) {
		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );
		$this->assertFalse( $lexemeContent->isValid() );
	}

	public function provideInvalidLexeme() {
		yield [ new Lexeme( new LexemeId( 'L1' ) ) ];
		yield [ new Lexeme( new LexemeId( 'L1' ), null, new ItemId( 'Q120' ), new ItemId( 'Q121' ) ) ];
		yield [ new Lexeme( new LexemeId( 'L1' ), null, null, new ItemId( 'Q121' ) ) ];
		yield [ new Lexeme( new LexemeId( 'L1' ), null, new ItemId( 'Q120' ) ) ];
	}

	/**
	 * @dataProvider provideGetPatchedCopy
	 */
	public function testGetPatchedCopy( Lexeme $lexeme, LexemeDiff $lexemeDiff, $assertions ) {
		$content = new LexemeContent( new EntityInstanceHolder( $lexeme ) );
		$contentDiff = new EntityContentDiff( $lexemeDiff, new Diff( [] ), $lexeme->getType() );
		$patchedCopy = $content->getPatchedCopy( $contentDiff );
		$patchedEntity = $patchedCopy->getEntity();
		$assertions( $lexeme, $patchedEntity );
	}

	public function provideGetPatchedCopy() {
		$lexemeDiffer = new LexemeDiffer();

		$newLexemeL1 = NewLexeme::havingId( 'L1' );
		$newFormF1 = NewForm::havingId( new FormId( 'L1-F1' ) );

		$newFormF1FeatureQ1 = $newFormF1->andGrammaticalFeature( new ItemId( 'Q1' ) );
		$newFormF1FeatureQ2 = $newFormF1->andGrammaticalFeature( new ItemId( 'Q2' ) );
		$newFormF1FeatureQ1andQ2 = $newFormF1->andGrammaticalFeature( new ItemId( 'Q1' ) )
			->andGrammaticalFeature( new ItemId( 'Q2' ) );

		// Build forms for use throughout that will have the same automatic representation added
		$formF1FeatureQ1 = $newFormF1FeatureQ1->build();
		$formF1FeatureQ2 = $newFormF1FeatureQ2->build();
		$formF1FeatureQ1andQ2 = $newFormF1FeatureQ1andQ2->build();

		$lexemeEmpty = $newLexemeL1->build();
		$lexemeFormFeatureQ1 = $newLexemeL1->withForm( $formF1FeatureQ1 )->build();
		$lexemeFormFeatureQ2 = $newLexemeL1->withForm( $formF1FeatureQ2 )->build();
		$lexemeFormFeatureQ1andQ2 = $newLexemeL1->withForm( $formF1FeatureQ1andQ2 )->build();

		yield 'Minimal entities, empty diff, should still be empty' => [
			$lexemeEmpty,
			$lexemeDiffer->diffEntities( $lexemeEmpty, $lexemeEmpty ),
			function ( Lexeme $lexeme, Lexeme $lexemeCopy ) {
				$this->assertTrue( $lexemeCopy->equals( $lexeme ) );
			},
		];
		yield 'Entities with the same form, empty diff, should remain unchanged' => [
			$lexemeFormFeatureQ1,
			$lexemeDiffer->diffEntities( $lexemeFormFeatureQ1, $lexemeFormFeatureQ1 ),
			function ( Lexeme $lexeme, Lexeme $lexemeCopy ) {
				$this->assertTrue( $lexemeCopy->equals( $lexeme ) );
			},
		];
		yield 'Adding a form feature (Q2)' => [
			$lexemeFormFeatureQ1,
			$lexemeDiffer->diffEntities( $lexemeFormFeatureQ1, $lexemeFormFeatureQ1andQ2 ),
			function ( Lexeme $lexeme, Lexeme $lexemeCopy ) use ( $lexemeFormFeatureQ1andQ2 ) {
				$this->assertFalse( $lexemeCopy->equals( $lexeme ) );
				$this->assertTrue( $lexemeCopy->equals( $lexemeFormFeatureQ1andQ2 ) );
			},
		];
		yield 'Removing a form feature (Q2)' => [
			$lexemeFormFeatureQ1andQ2,
			$lexemeDiffer->diffEntities( $lexemeFormFeatureQ1andQ2, $lexemeFormFeatureQ1 ),
			function ( Lexeme $lexeme, Lexeme $lexemeCopy ) use ( $lexemeFormFeatureQ1 ) {
				$this->assertFalse( $lexemeCopy->equals( $lexeme ) );
				$this->assertTrue( $lexemeCopy->equals( $lexemeFormFeatureQ1 ) );
			},
		];
		yield 'Changing a form feature (Q1 -> Q2)' => [
			$lexemeFormFeatureQ1,
			$lexemeDiffer->diffEntities( $lexemeFormFeatureQ1, $lexemeFormFeatureQ2 ),
			function ( Lexeme $lexeme, Lexeme $lexemeCopy ) use ( $lexemeFormFeatureQ2 ) {
				$this->assertFalse( $lexemeCopy->equals( $lexeme ) );
				$this->assertTrue( $lexemeCopy->equals( $lexemeFormFeatureQ2 ) );
			},
		];
	}

	/**
	 * @dataProvider provideLexemesWithStatementCount
	 */
	public function testGetEntityPageProperties( NewLexeme $lexeme, $expectedCount ) {
		$content = new LexemeContent( new EntityInstanceHolder( $lexeme->build() ) );

		$pageProps = $content->getEntityPageProperties();

		$this->assertSame( $expectedCount, (int)$pageProps['wb-claims'] );
	}

	public function provideLexemesWithStatementCount() {
		yield 'empty lexeme' => [ NewLexeme::create(), 0 ];

		$snak = new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) );
		$lexeme = NewLexeme::create()->withStatement( $snak );
		yield 'one statement' => [ $lexeme, 1 ];
		yield 'two statements' => [ $lexeme->withStatement( $snak ), 2 ];

		$form = NewForm::any();
		yield 'empty form' => [ NewLexeme::havingForm( $form ), 0 ];
		yield 'one statement with empty form' => [ $lexeme->withForm( $form ), 1 ];

		$form = $form->andStatement( $snak );
		yield 'one statement and one form statement' => [ $lexeme->withForm( $form ), 2 ];
		$form = $form->andStatement( $snak );
		yield 'one statement and two form statements' => [ $lexeme->withForm( $form ), 3 ];

		$sense = NewSense::havingStatement( $snak );
		yield 'one statement and one sense statement' => [ $lexeme->withSense( $sense ), 2 ];

		$lexeme = $lexeme->withStatement( $snak )->withForm( $form )->withSense( $sense );
		$form = $form->andStatement( $snak );
		$sense = $sense->withStatement( $snak )->withStatement( $snak )->withStatement( $snak );
		$lexeme = $lexeme->withForm( $form )->withSense( $sense );
		yield '2 statements, 2+3 form statements, 1+4 sense statements' => [ $lexeme, 12 ];
	}

	/**
	 * @dataProvider provideLexemesWithSensesAndForms
	 */
	public function testGetEntityPagePropertiesSensesAndForms(
		NewLexeme $lexeme,
		$expectedSensesCount,
		$expectedFormsCount
	) {
		$content = new LexemeContent( new EntityInstanceHolder( $lexeme->build() ) );

		$pageProps = $content->getEntityPageProperties();

		$this->assertSame( $expectedSensesCount, (int)$pageProps['wbl-senses'] );
		$this->assertSame( $expectedFormsCount, (int)$pageProps['wbl-forms'] );
	}

	public function provideLexemesWithSensesAndForms() {
		yield 'empty lexeme' => [ NewLexeme::create(), 0, 0 ];

		$lexeme = NewLexeme::create();
		$form = NewForm::any();
		yield 'one form' => [ $lexeme->withForm( $form ), 0, 1 ];

		$sense = NewSense::havingId( 'S1' );
		yield 'one sense' => [ $lexeme->withSense( $sense ), 1, 0 ];

		$lexeme = NewLexeme::create()
			->withForm( $form )
			->withSense( $sense )
			->withSense( NewSense::havingId( 'S2' ) );
		yield 'two senses, one form' => [ $lexeme, 2, 1 ];
	}

	public function testSearchIndex() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withLemma( 'en', 'test' )
			->withLemma( 'en-gb', 'moretest' )
			->withLemma( 'ru', 'тест' )
			->withLexicalCategory( 'Q120' )
			->withLanguage( 'Q121' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'form' )
					->andRepresentation( 'en-gb', 'moreform' )
					->andRepresentation( 'ru', 'форма' )
			)
			->withForm(
				NewForm::havingId( 'F2' )
					->andRepresentation( 'en', 'form2' )
					->andRepresentation( 'en-gb', 'moreform2' )
					->andRepresentation( 'ru', 'форма2' )
			)
			->build();

		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );

		$data = $lexemeContent->getTextForSearchIndex();
		$this->assertEquals( "test moretest тест form moreform форма form2 moreform2 форма2", $data );
	}

	public function testGetTextForFilters() {
		$entity = new Lexeme(
			new LexemeId( 'L123' ),
			new TermList( [ new Term( 'en', 'lemma1' ), new Term( 'de', 'lemma2' ) ] ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			new StatementList(
				new Statement(
					new PropertyValueSnak(
						new NumericPropertyId( 'P6654' ), new StringValue( 'stringvalue' )
					),
					new SnakList(
						[
							new PropertyValueSnak(
								new NumericPropertyId( 'P6654' ),
								new GlobeCoordinateValue( new LatLongValue( 1, 2 ), 1 )
							),
							new PropertyValueSnak(
								new NumericPropertyId( 'P6654' ),
								new TimeValue(
									'+2015-11-11T00:00:00Z',
									0,
									0,
									0,
									TimeValue::PRECISION_DAY,
									TimeValue::CALENDAR_GREGORIAN
								)
							),
						]
					),
					new ReferenceList(
						[
							new Reference(
								[
									new PropertySomeValueSnak( new NumericPropertyId( 'P987' ) ),
									new PropertyNoValueSnak( new NumericPropertyId( 'P986' ) )
								]
							)
						]
					),
					'imaguid'
				)
			),
			6661,
			new FormSet( [
				new Form(
					new FormId( 'L123-F123' ),
					new TermList( [ new Term( 'en', 'rep1' ), new Term( 'de', 'rep2' ) ] ),
					[ new ItemId( 'Q553' ), new ItemId( 'Q554' ) ]
				)
			] ),
			6662,
			new SenseSet( [
				new Sense(
					new SenseId( 'L123-S123' ),
					new TermList( [ new Term( 'en', 'gloss1' ), new Term( 'de', 'gloss2' ) ] )
				)
			] )
		);

		$content = new LexemeContent( new EntityInstanceHolder( $entity ) );
		$output = $content->getTextForFilters();

		$this->assertSame(
			trim( file_get_contents( __DIR__ . '/textForFilters.txt' ) ),
			$output
		);
	}

	/**
	 * @dataProvider lexemeTextSummaryProvider
	 */
	public function testGetTextSummary( $lexeme, $redirect, $redirectTitle, $expectedOutput, $length ) {
		$lexemeContent = new LexemeContent(
			$lexeme === null ? null : new EntityInstanceHolder( $lexeme ),
			$redirect,
			$redirectTitle
		);

		$this->assertEquals( $expectedOutput, $lexemeContent->getTextForSummary( $length ) );
	}

	public function lexemeTextSummaryProvider() {
		return [
			'normal behaviour' => [
				NewLexeme::havingId( 'L1' )
					->withLemma( 'sv', 'ett' )
					->withLemma( 'en-gb', 'two' )
					->withLemma( 'de', 'drei' )
					->withForm(
						NewForm::havingId( 'F1' )
							->andRepresentation( 'en', 'should' )
							->andRepresentation( 'en-gb', 'not' )
							->andRepresentation( 'en-us', 'show up' )
					)
					->build(),
				null,
				null,
				'ett, two, drei',
				250
			],
			'cuts off the text' => [
				NewLexeme::havingId( 'L2' )
					->withLemma( 'sv', 'some thing really long that should get cut off eventually' )
					->build(),
				null,
				null,
				'some thing...',
				13
			],
			'returns nothing if no lemmas' => [
				new Lexeme(),
				null,
				null,
				'',
				250
			],
			'redirect' => [
				null,
				new EntityRedirect( new LexemeId( 'L1' ), new LexemeId( 'L2' ) ),
				Title::newFromText( 'redirectTitle' ),
				'#REDIRECT [[RedirectTitle]]',
				250
			]
		];
	}

}
