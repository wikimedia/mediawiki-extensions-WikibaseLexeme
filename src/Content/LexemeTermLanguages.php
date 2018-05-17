<?php

namespace Wikibase\Lexeme\Content;

use Language;
use Wikibase\Lib\ContentLanguages;

/**
 * @license GPL-2.0-or-later
 */
class LexemeTermLanguages implements ContentLanguages {

	/**
	 * @var string[] Additional language codes beyond the Mediawiki's Language ones
	 */
	private $additionalLanguageCodes;

	/**
	 * @var string[]|null Array of language codes
	 */
	private $languages = null;

	/**
	 * @param array $additionalLanguageCodes Codes beyond the Mediawiki's Language ones
	 */
	public function __construct( array $additionalLanguageCodes ) {
		$this->additionalLanguageCodes = $additionalLanguageCodes;
	}

	/**
	 * @return string[] Array of language codes supported as content language
	 */
	public function getLanguages() {
		return $this->getLanguageCodes();
	}

	/**
	 * @param string $languageCode
	 *
	 * @return bool
	 */
	public function hasLanguage( $languageCode ) {
		return in_array( $languageCode, $this->getLanguageCodes() );
	}

	/**
	 * @return string[] Array of language codes
	 */
	private function getLanguageCodes() {
		if ( $this->languages === null ) {
			$this->languages = $this->buildLanguageCodes();
		}

		return $this->languages;
	}

	private function buildLanguageCodes() {
		$defaultCodes = array_keys( Language::fetchLanguageNames() );
		$codes = array_merge( $defaultCodes, $this->additionalLanguageCodes );
		$codes = array_unique( $codes );
		sort( $codes );
		return $codes;
	}

}
