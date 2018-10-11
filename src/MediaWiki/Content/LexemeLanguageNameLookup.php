<?php

namespace Wikibase\Lexeme\MediaWiki\Content;

use MessageLocalizer;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @license GPL-2.0-or-later
 */
class LexemeLanguageNameLookup {

	/**
	 * @var MessageLocalizer
	 */
	private $messageLocalizer;

	/**
	 * @var string[]
	 */
	private $additionalLanguageCodes;

	/**
	 * @var LanguageNameLookup
	 */
	private $fallbackLookup;

	public function __construct(
		MessageLocalizer $messageLocalizer,
		array $additionalLanguageCodes,
		LanguageNameLookup $fallbackLookup
	) {
		$this->messageLocalizer = $messageLocalizer;
		$this->additionalLanguageCodes = $additionalLanguageCodes;
		$this->fallbackLookup = $fallbackLookup;
	}

	/**
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function getName( $languageCode ) {
		if ( in_array( $languageCode, $this->additionalLanguageCodes ) ) {
			return $this->messageLocalizer
				->msg( 'wikibase-lexeme-language-name-' . $languageCode )
				->plain();
		}

		return $this->fallbackLookup->getName( $languageCode );
	}

}
