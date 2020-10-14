<?php

declare( strict_types=1 );
namespace Wikibase\Lexeme\Presentation\Content;

use Language;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class LemmaTextSummaryFormatter {

	/**
	 * @var Language
	 */
	private $language;

	public function __construct( $contentLanguage ) {
		$this->language = $contentLanguage;
	}

	/**
	 * @param TermList $lemmas
	 * @param int $maxLength
	 * @return string
	 */
	public function getSummary( TermList $lemmas, int $maxLength ) {
		if ( $lemmas->isEmpty() ) {
			return '';
		}

		// Note: this assumes that only one lemma per language exists
		$terms = array_values( $lemmas->toTextArray() );

		return $this->language->truncateForDatabase(
			$this->language->commaList( $terms ),
			$maxLength
		);
	}

}
