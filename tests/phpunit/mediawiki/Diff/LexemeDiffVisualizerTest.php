<?php

namespace Wikibase\Lexeme\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use HashSiteStore;
use MediaWikiTestCase;
use MessageLocalizer;
use RawMessage;
use Site;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiff;
use Wikibase\Lexeme\Diff\LexemeDiffVisualizer;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;

/**
 * @covers Wikibase\Lexeme\Diff\LexemeDiffVisualizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class LexemeDiffVisualizerTest extends MediaWikiTestCase {

	public function testVisualizingEmptyDiff() {
		$emptyDiff = new EntityContentDiff( new EntityDiff(), new Diff(), 'lexeme' );
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $emptyDiff );

		$this->assertSame( '', $html );
	}

	public function diffProvider() {
		$lexemeDiff = new EntityContentDiff(
			new LexemeDiff( [
				'lemmas' => new Diff( [
					'en' => new DiffOpAdd( 'O_o' ),
				], true ),

				'lexicalCategory' => new Diff( [
					'id' => new DiffOpRemove( 'Q2' ),
				], true ),

				'language' => new Diff( [
					'id' => new DiffOpChange( 'Q3', 'Q4' ),
				], true ),
			] ),
			new Diff(),
			'lexeme'
		);

		$lexemeTags = [
			'has <td>lemma / en</td>' => '>(wikibaselexeme-diffview-lemma) / en</td>',
			'has <ins>O_o</ins>' => '>O_o</ins>',
			'has <td> lexical category / id</td>' => '>(wikibaselexeme-diffview-lexical-category) / id</td>',
			'has <del>Q2</del>' => '>Q2</del>',
			'has <td>language / id</td>' => '>(wikibaselexeme-diffview-language) / id</td>',
			'has <del>Q3</del>' => '>Q3</del>',
			'has <ins>Q4</ins>' => '>Q4</ins>',
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

	/**
	 * @return LexemeDiffVisualizer
	 */
	private function getVisualizer() {
		$enwiki = new Site();
		$enwiki->setGlobalId( 'enwiki' );

		$baiscVisualizer = new BasicEntityDiffVisualizer(
			$this->getMockMessageLocalizer(),
			$this->getMockClaimDiffer(),
			$this->getMockClaimDiffVisualizer(),
			new HashSiteStore( [ $enwiki ] ),
			$this->getMock( EntityIdFormatter::class )
		);

		return new LexemeDiffVisualizer(
			$this->getMockMessageLocalizer(),
			$baiscVisualizer
		);
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGenerateEntityContentDiffBody( EntityContentDiff $diff, array $matchers ) {
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $diff );

		$this->assertInternalType( 'string', $html );
		foreach ( $matchers as $name => $matcher ) {
			$this->assertContains( $matcher, $html, $name );
		}
	}

}
