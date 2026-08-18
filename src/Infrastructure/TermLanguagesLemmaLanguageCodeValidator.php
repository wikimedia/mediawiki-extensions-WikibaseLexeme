<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Infrastructure;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Validation\LemmaLanguageCodeValidator;
use Wikibase\Lib\ContentLanguages;

/**
 * @license GPL-2.0-or-later
 */
class TermLanguagesLemmaLanguageCodeValidator implements LemmaLanguageCodeValidator {

	/**
	 * According to BCP 47 (https://tools.ietf.org/html/bcp47)
	 */
	private const PRIVATE_USE_SUBTAG_SEPARATOR = '-x-';

	public function __construct( private ContentLanguages $languages ) {
	}

	public function isValid( string $languageCode ): bool {
		$parts = explode( self::PRIVATE_USE_SUBTAG_SEPARATOR, $languageCode, 2 );
		$language = $parts[0];

		if ( $language === '' || !$this->languages->hasLanguage( $language ) ) {
			return false;
		}

		return count( $parts ) === 1 || $this->isValidItemId( $parts[1] );
	}

	private function isValidItemId( string $id ): bool {
		return preg_match( ItemId::PATTERN, $id ) &&
			strtoupper( $id ) === $id;
	}

}
