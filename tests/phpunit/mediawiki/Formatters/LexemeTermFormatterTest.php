<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Formatters;

use HamcrestPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;

/**
 * @covers \Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter
 * @license GPL-2.0-or-later
 */
class LexemeTermFormatterTest extends TestCase {
	use HamcrestPHPUnitIntegration;

	public function testGivenSingleLemma_formatsWithLangAttributes() {
		$formatter = $this->newFormatter();

		$this->assertThatHamcrest(
			$formatter->format( new TermList( [ new Term( 'en', 'potato' ) ] ) ),
			is( htmlPiece(
				havingRootElement( both(
					tagMatchingOutline(
						'<span lang="en" dir="ltr">'
					)
				)->andAlso(
					havingTextContents( 'potato' )
				) )
			) )
		);
	}

	public function testGivenMultipleLemmas_concatenatesWithSeparatorAndLangAttributes() {
		$separator = '---';
		$formatter = $this->newFormatter( $separator );
		$lemma1 = 'color';
		$lemma2 = 'colour';
		$lang1 = 'en';
		$lang2 = 'en-GB';
		$lemmas = new TermList( [ new Term( $lang1, $lemma1 ), new Term( $lang2, $lemma2 ) ] );

		$this->assertThatHamcrest(
			$this->wrapInDiv( $formatter->format( $lemmas ) ),
			is( htmlPiece( havingRootElement( allOf(
				havingChild( both(
					tagMatchingOutline( "<span lang=\"$lang1\"></span>" ) )
					->andAlso( havingTextContents( $lemma1 ) )
				),
				havingChild( both(
					tagMatchingOutline( "<span lang=\"$lang2\"></span>" ) )
					->andAlso( havingTextContents( $lemma2 ) )
				),
				havingTextContents( equalToIgnoringWhiteSpace(
					$lemma1 . $separator . $lemma2
				) )
			) ) ) )
		);
	}

	public function testGivenLemmaInRtlLanguage_usesRtlDir() {
		$lemma = 'صِفْر';
		$formatter = $this->newFormatter();

		$this->assertThatHamcrest(
			$formatter->format( new TermList( [ new Term( 'ar', $lemma ) ] ) ),
			is( htmlPiece(
				havingChild( both(
					tagMatchingOutline( '<span dir="rtl" class="mw-content-rtl"></span>' ) )
					->andAlso( havingTextContents( $lemma ) )
				)
			) )
		);
	}

	private function newFormatter( $separator = '' ) {
		return new LexemeTermFormatter( $separator );
	}

	// silly. workaround for hamcrest not dealing well with multiple root elements
	private function wrapInDiv( $html ) {
		return "<div>$html</div>";
	}

}
