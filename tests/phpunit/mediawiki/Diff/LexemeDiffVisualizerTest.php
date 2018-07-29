<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use HamcrestPHPUnitIntegration;
use HashSiteStore;
use MediaWikiTestCase;
use MessageLocalizer;
use RawMessage;
use Site;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Services\Diff\ChangeFormDiffOp;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiff;
use Wikibase\Lexeme\Diff\LexemeDiffVisualizer;
use Wikibase\Lexeme\Diff\ItemReferenceDifferenceVisualizer;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;

/**
 * @covers \Wikibase\Lexeme\Diff\LexemeDiffVisualizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class LexemeDiffVisualizerTest extends MediaWikiTestCase {
	use HamcrestPHPUnitIntegration;

	public function testVisualizingEmptyDiff() {
		$emptyDiff = new EntityContentDiff( new EntityDiff(), new Diff(), 'lexeme' );
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $emptyDiff );

		$this->assertSame( '', $html );
	}

	public function diffProvider() {
		$formDiff = new ChangeFormDiffOp(
			new FormId( 'L1-F1' ),
			new Diff( [
				'representations' => new Diff( [ 'en' => new DiffOpChange( 'old', 'new' ) ], true ),
				'grammaticalFeatures' => new DiffOpChange( new ItemId( 'Q5' ), new ItemId( 'Q6' ) )
			], true )
		);
		$lexemeDiff = new EntityContentDiff(
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
				'forms' => new Diff( [
					'L1-F1' => $formDiff,
				], true ),
			] ),
			new Diff(),
			'lexeme'
		);

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
			'has <del>old</del>' => '>old</del>',
			'has <ins>new</ins>' => '>new</ins>',
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

	/**
	 * @return MessageLocalizer
	 */
	private function getMockMessageLocalizer() {
		$mock = $this->getMock( MessageLocalizer::class );

		$mock->method( 'msg' )
			->will( $this->returnCallback( function ( $key ) {
				return new RawMessage( "($key)" );
			} ) );

		return $mock;
	}

	/**
	 * @return ClaimDiffer
	 */
	private function getMockClaimDiffer() {
		$mock = $this->getMockBuilder( ClaimDiffer::class )
			->disableOriginalConstructor()
			->getMock();
		return $mock;
	}

	/**
	 * @return ClaimDifferenceVisualizer
	 */
	private function getMockClaimDiffVisualizer() {
		$mock = $this->getMockBuilder( ClaimDifferenceVisualizer::class )
			->disableOriginalConstructor()
			->getMock();
		return $mock;
	}

	private function getIdFormatter() {
		$formatter = $this->getMock( EntityIdFormatter::class );
		$formatter->method( $this->anything() )
			->willReturnCallback( function ( EntityId $entityId ) {
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
			$this->getMockClaimDiffer(),
			$this->getMockClaimDiffVisualizer(),
			new HashSiteStore( [ $enwiki ] ),
			$this->getMock( EntityIdFormatter::class )
		);

		return new LexemeDiffVisualizer(
			$this->getMockMessageLocalizer(),
			$basicVisualizer,
			$this->getMockClaimDiffer(),
			$this->getMockClaimDiffVisualizer(),
			new ItemReferenceDifferenceVisualizer( $this->getIdFormatter() )
		);
	}

	/**
	 * TODO: This test should probably be redone?
	 *
	 * @dataProvider diffProvider
	 */
	public function testGenerateEntityContentDiffBody( EntityContentDiff $diff, array $matchers ) {
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $diff );

		$this->assertInternalType( 'string', $html );
		foreach ( $matchers as $name => $matcher ) {
			$this->assertContains( $matcher, $html, $name );
		}
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

		$this->assertThatHamcrest( $diffHtml, is( htmlPiece(
			havingChild( allOf(
				withTagName( 'tr' ),
				havingChild(
					both( tagMatchingOutline( '<td class="diff-deletedline"/>' ) )->andAlso(
						havingChild( both( withTagName( 'del' ) )->andAlso( havingTextContents( 'formatted Q2' ) ) )
					)
				),
				havingChild(
					both( tagMatchingOutline( '<td class="diff-addedline"/>' ) )->andAlso(
						havingChild( both( withTagName( 'ins' ) )->andAlso( havingTextContents( 'formatted Q3' ) ) )
					)
				)
			) )
		) ) );
	}

}
