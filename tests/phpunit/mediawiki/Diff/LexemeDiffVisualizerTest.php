<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use HamcrestPHPUnitIntegration;
use MediaWikiIntegrationTestCase;
use MessageLocalizer;
use RawMessage;
use Site;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lexeme\Domain\Diff\ChangeFormDiffOp;
use Wikibase\Lexeme\Domain\Diff\ChangeSenseDiffOp;
use Wikibase\Lexeme\Domain\Diff\LexemeDiff;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Presentation\Diff\ItemReferenceDifferenceVisualizer;
use Wikibase\Lexeme\Presentation\Diff\LexemeDiffVisualizer;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;

/**
 * @covers \Wikibase\Lexeme\Presentation\Diff\LexemeDiffVisualizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class LexemeDiffVisualizerTest extends MediaWikiIntegrationTestCase {
	use HamcrestPHPUnitIntegration;

	public function testVisualizingEmptyDiff() {
		$emptyDiff = new EntityContentDiff( new EntityDiff(), new Diff(), 'lexeme' );
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $emptyDiff );

		$this->assertSame( '', $html );
	}

	public function diffProvider() {
		$lexemeDiff = $this->getLexemeDiff();

		$expectedForm = '(wikibaselexeme-diffview-form) / L1-F1 / (wikibaselexeme-diffview-';
		$lexemeTags = [
			'has <td>lemma / en</td>' => '>(wikibaselexeme-diffview-lemma) / en</td>',
			'has <ins>O_o</ins>' => '>O_o</ins>',
			'has <td> lexical category </td>' => '>(wikibaselexeme-diffview-lexical-category)</td>',
			'has <del>Q2</del>' => '>formatted Q2</span></del>',
			'has <td>language</td>' => '>(wikibaselexeme-diffview-language)</td>',
			'has <del>Q3</del>' => '>formatted Q3</span></del>',
			'has <ins>Q4</ins>' => '>formatted Q4</span></ins>',
			'has form representation' => $expectedForm . 'representation) / en<',
			'has <del>old</del>' => '>oldFormRepresentation</del>',
			'has <ins>new</ins>' => '>newFormRepresentation</ins>',
			'has form grammatical-feature' => $expectedForm . 'grammatical-feature)',
			'has <del>Q5</del>' => '>formatted Q5</span></del>',
			'has <ins>Q6</ins>' => '>formatted Q6</span></ins>',
		];

		$redirectDiff = new EntityContentDiff(
			new LexemeDiff(),
			new Diff( [ 'redirect' => new DiffOpAdd( 'L1234' ) ], true ),
			'lexeme'
		);

		$redirectTags = [
			'has <td>redirect</td>' => '>redirect</td>',
			'has <ins>L1234</ins>' => '>L1234</ins>',
		];

		return [
			'lexeme changed' => [ $lexemeDiff, $lexemeTags ],
			'redirect changed' => [ $redirectDiff, $redirectTags ],
		];
	}

	private function getLexemeDiff() {
		return new EntityContentDiff(
			new LexemeDiff( [
				'lemmas' => new Diff( [
					'en' => new DiffOpAdd( 'O_o' ),
				], true ),

				'lexicalCategory' => new Diff( [
					'id' => new DiffOpRemove( new ItemId( 'Q2' ) ),
				], true ),

				'language' => new Diff( [
					'id' => new DiffOpChange( new ItemId( 'Q3' ), new ItemId( 'Q4' ) ),
				], true ),

				'claim' => new Diff( [
					new DiffOpAdd(
						NewStatement::forProperty( 'P1' )->withValue( 'foo' )->build()
					) ] ),

				'forms' => new Diff( [
					'L1-F1' => $this->getFormDiff(),
				], true ),

				'senses' => new Diff( [
					'L1-S1' => $this->getSensesDiff(),
					], true )
			] ),
			new Diff(),
			'lexeme'
		);
	}

	private function getFormDiff() {
		return new ChangeFormDiffOp(
			new FormId( 'L1-F1' ),
			new Diff( [
				'representations' => new Diff( [
					'en' => new DiffOpChange( 'oldFormRepresentation', 'newFormRepresentation' )
				], true ),
				'grammaticalFeatures' => new DiffOpChange( new ItemId( 'Q5' ), new ItemId( 'Q6' ) )
			], true )
		);
	}

	private function getSensesDiff() {
		return new ChangeSenseDiffOp(
			new SenseId( 'L1-S1' ),
			new Diff( [
				'glosses' => new Diff( [ 'en' => new DiffOpChange( 'oldGloss', 'newGloss' ) ], true ),
			], true )
		);
	}

	/**
	 * @return MessageLocalizer
	 */
	private function getMockMessageLocalizer() {
		$mock = $this->createMock( MessageLocalizer::class );

		$mock->method( 'msg' )
			->will( $this->returnCallback( static function ( $key ) {
				return new RawMessage( "($key)" );
			} ) );

		return $mock;
	}

	/**
	 * @return ClaimDifferenceVisualizer
	 */
	private function getMockClaimDiffVisualizer() {
		$mockNewClaimHTML = '<tr>SOME_CLAIM_DIFF_ROW</tr>';
		$mock = $this->createMock( ClaimDifferenceVisualizer::class );
		$mock->method( 'visualizeNewClaim' )
			->willReturn( $mockNewClaimHTML );
		return $mock;
	}

	private function getIdFormatter() {
		$formatter = $this->createMock( EntityIdFormatter::class );
		$formatter->method( $this->anything() )
			->willReturnCallback( static function ( EntityId $entityId ) {
				$id = $entityId->getSerialization();
				return 'formatted ' . $id;
			} );
		return $formatter;
	}

	/**
	 * @return LexemeDiffVisualizer
	 */
	private function getVisualizer() {
		$enwiki = new Site();
		$enwiki->setGlobalId( 'enwiki' );

		$basicVisualizer = new BasicEntityDiffVisualizer(
			$this->getMockMessageLocalizer(),
			$this->createMock( ClaimDiffer::class ),
			$this->getMockClaimDiffVisualizer()
		);

		return new LexemeDiffVisualizer(
			$this->getMockMessageLocalizer(),
			$basicVisualizer,
			$this->createMock( ClaimDiffer::class ),
			$this->getMockClaimDiffVisualizer(),
			new ItemReferenceDifferenceVisualizer( $this->getIdFormatter() )
		);
	}

	public function testGeneratesLemmaHtml() {
		$lexemeDiff = new LexemeDiff(
			[
				'lemmas' => new Diff(
					[
						'en' => new DiffOpAdd( 'NewLemma' ),
					], true
				)
			]
		);
		$entityContentDiff = new EntityContentDiff( $lexemeDiff, new Diff(), 'lexeme' );
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $entityContentDiff );
		$this->assertThatHamcrest(
			$html,
			$this->getAddRowHamcrestMatcher( 'NewLemma' )
		);
	}

	private function getAddRowHamcrestMatcher( $textContents ) {
		return htmlPiece( havingChild( allOf(
				withTagName( 'tr' ),
				havingChild(
					both( tagMatchingOutline( '<td class="diff-addedline"/>' ) )->andAlso(
						havingChild(
							both( withTagName( 'ins' ) )->andAlso( havingTextContents( $textContents ) )
						)
					)
				)
			) ) );
	}

	public function testGeneratesLexicalCatHtml() {
		$lexemeDiff = new LexemeDiff(
			[
				'lexicalCategory' => new Diff(
					[
						'id' => new DiffOpRemove( new ItemId( 'Q2' ) ),
					], true
				) ]
		);
		$entityContentDiff = new EntityContentDiff( $lexemeDiff, new Diff(), 'lexeme' );
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $entityContentDiff );
		$this->assertThatHamcrest(
			$html,
			$this->getDeleteRowHamcrestMatcher( 'formatted Q2' )
		);
	}

	private function getDeleteRowHamcrestMatcher( $textContents ) {
		return htmlPiece( havingChild( allOf(
				withTagName( 'tr' ),
				havingChild(
					both( tagMatchingOutline( '<td class="diff-deletedline"/>' ) )->andAlso(
						havingChild(
							both( withTagName( 'del' ) )->andAlso( havingTextContents( $textContents ) )
						)
					)
				)
			) ) );
	}

	public function testGeneratesLanguageHtml() {
		$lexemeDiff = new LexemeDiff(
			[
				'language' => new Diff( [
					'id' => new DiffOpChange( new ItemId( 'Q3' ), new ItemId( 'Q4' ) ),
				], true )
			]
		);
		$entityContentDiff = new EntityContentDiff( $lexemeDiff, new Diff(), 'lexeme' );
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $entityContentDiff );
		$this->assertThatHamcrest(
			$html,
			$this->getDeleteRowHamcrestMatcher( 'formatted Q3' )
		);
		$this->assertThatHamcrest(
			$html,
			$this->getAddRowHamcrestMatcher( 'formatted Q4' )
		);
	}

	public function testGeneratesClaimHtml() {
		$lexemeDiff = new LexemeDiff( [
			'claim' => new Diff( [
				new DiffOpAdd( NewStatement::forProperty( 'P1' )->withValue( 'foo' )->build() )
			] ) ] );
		$entityContentDiff = new EntityContentDiff( $lexemeDiff, new Diff(), 'lexeme' );
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $entityContentDiff );
		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild(
				both( withTagName( 'tr' ) )->andAlso(
					havingTextContents( 'SOME_CLAIM_DIFF_ROW' )
				) ) ) )
			);
	}

	public function testGeneratesFormRepresentationHtml() {
		$formDiff = new ChangeFormDiffOp(
			new FormId( 'L1-F3141' ),
			new Diff( [
				'representations' => new Diff( [
					'en' => new DiffOpChange( 'oldFormRepresentation', 'newFormRepresentation' )
				], true )
			] )
		);
		$lexemeDiff = new LexemeDiff(
			[
				'forms' => new Diff( [
					'L1-F3141' => $formDiff,
				], true )
			]
		);
		$entityContentDiff = new EntityContentDiff( $lexemeDiff, new Diff(), 'lexeme' );
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $entityContentDiff );
		$this->assertThatHamcrest( $html, $this->getDeleteRowHamcrestMatcher( 'oldFormRepresentation' ) );
		$this->assertThatHamcrest( $html, $this->getAddRowHamcrestMatcher( 'newFormRepresentation' ) );
	}

	public function testGeneratesFormGramFeatHtml() {
		$formDiff = new ChangeFormDiffOp(
			new FormId( 'L1-F2718' ),
			new Diff( [
				'grammaticalFeatures' => new DiffOpChange( new ItemId( 'Q5' ), new ItemId( 'Q6' ) )
			] )
		);
		$lexemeDiff = new LexemeDiff(
			[
				'forms' => new Diff( [
					'L1-F2718' => $formDiff,
				], true )
			]
		);
		$entityContentDiff = new EntityContentDiff( $lexemeDiff, new Diff(), 'lexeme' );
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $entityContentDiff );
		$this->assertThatHamcrest( $html, $this->getDeleteRowHamcrestMatcher( 'formatted Q5' ) );
		$this->assertThatHamcrest( $html, $this->getAddRowHamcrestMatcher( 'formatted Q6' ) );
	}

	public function testGeneratesRedirectHtml() {
		$entityContentDiff = new EntityContentDiff(
			new LexemeDiff(), new Diff( [ 'redirect' => new DiffOpAdd( 'L6427' ) ] ), 'lexeme'
		);
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $entityContentDiff );
		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild( both(
				withTagName( 'td' ) )->andAlso(
					havingTextContents( 'redirect' )
			) ) ) )
		);
		$this->assertThatHamcrest(
			$html,
			$this->getAddRowHamcrestMatcher( 'L6427' )
		);
	}

	public function testGivenLexicalCategoryChanged_diffDisplaysChangedItemsAsFormattedItems() {
		$diff = new EntityContentDiff(
			new LexemeDiff( [
				'lexicalCategory' => new Diff(
					[ 'id' => new DiffOpChange( new ItemId( 'Q2' ), new ItemId( 'Q3' ) ) ],
					true
				),
			] ),
			new Diff(),
			'lexeme'
		);

		$diffHtml = $this->getVisualizer()->visualizeEntityContentDiff( $diff );

		$this->assertThatHamcrest( $diffHtml,  $this->getAddRowHamcrestMatcher( 'formatted Q3' ) );
		$this->assertThatHamcrest( $diffHtml,  $this->getDeleteRowHamcrestMatcher( 'formatted Q2' ) );
	}

	public function testGenerateEntityContentDiffOrder() {
		$lexemeEntityContentDiff = $this->getLexemeDiff();
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $lexemeEntityContentDiff );
		$this->assertThatHamcrest( $html, stringContainsInOrder(
			[
				'(wikibaselexeme-diffview-lemma)',
				'(wikibaselexeme-diffview-lexical-category)',
				'(wikibaselexeme-diffview-language)',
				'SOME_CLAIM_DIFF_ROW',
				'(wikibaselexeme-diffview-sense)',
				'(wikibaselexeme-diffview-gloss)',
				'(wikibaselexeme-diffview-form)',
				'(wikibaselexeme-diffview-representation)',
				'(wikibaselexeme-diffview-grammatical-feature)',
			]
		) );
	}

}
