<?php

namespace Wikibase\Lexeme\Presentation\Formatters;

use Html;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class LexemeTermFormatter {

	/** @var string */
	private $separator;

	public function __construct( $separator ) {
		$this->separator = $separator;
	}

	/**
	 * Formats lexeme terms (lemmas, representations) as HTML.
	 * Returns an empty string when given an empty TermList.
	 *
	 * @param TermList $terms
	 *
	 * @return string HTML
	 */
	public function format( TermList $terms ) {
		return implode(
			$this->separator,
			array_map(
				[ $this, 'getTermHtml' ],
				iterator_to_array( $terms->getIterator() )
			)
		);
	}

	private function getTermHtml( Term $term ) {
		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $term->getLanguageCode() );

		return Html::element(
			'span',
			[
				'class' => 'mw-content-' . $language->getDir(),
				'dir' => $language->getDir(),
				'lang' => $language->getHtmlCode(),
			],
			$term->getText()
		);
	}

}
