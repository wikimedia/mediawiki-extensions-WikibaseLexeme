<?php

namespace Wikibase\Lexeme\Content;

use MessageLocalizer;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @license GPL-2.0-or-later
 */
class LexemeLanguageNameLookup extends LanguageNameLookup {

	/**
	 * @var MessageLocalizer
	 */
	private $messageLocalizer;

	/**
	 * @var string[]
	 */
	private $additionalLanguageCodes;

	public function __construct(
		$inLanguage = null,
		MessageLocalizer $messageLocalizer,
		array $additionalLanguageCodes
	) {
		$this->messageLocalizer = $messageLocalizer;
		$this->additionalLanguageCodes = $additionalLanguageCodes;

		parent::__construct( $inLanguage );
	}

	public function getName( $languageCode ) {
		if ( in_array( $languageCode, $this->additionalLanguageCodes ) ) {
			return $this->messageLocalizer->msg( 'wikibase-lexeme-language-name-' . $languageCode );
		}

		return parent::getName( $languageCode );
	}

}
