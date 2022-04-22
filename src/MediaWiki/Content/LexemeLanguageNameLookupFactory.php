<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Content;

use IContextSource;
use MessageLocalizer;
use Wikibase\Lib\LanguageNameLookupFactory;

/**
 * @license GPL-2.0-or-later
 */
class LexemeLanguageNameLookupFactory {

	/** @var LanguageNameLookupFactory */
	private $languageNameLookupFactory;

	/** @var string[] */
	private $additionalLanguages;

	public function __construct(
		LanguageNameLookupFactory $languageNameLookupFactory,
		array $additionalLanguages
	) {
		$this->languageNameLookupFactory = $languageNameLookupFactory;
		$this->additionalLanguages = $additionalLanguages;
	}

	public function getForLanguageCodeAndMessageLocalizer(
		string $languageCode,
		MessageLocalizer $messageLocalizer
	): LexemeLanguageNameLookup {
		return new LexemeLanguageNameLookup(
			$messageLocalizer,
			$this->additionalLanguages,
			$this->languageNameLookupFactory->getForLanguageCode( $languageCode )
		);
	}

	public function getForContextSource(
		IContextSource $context
	): LexemeLanguageNameLookup {
		return $this->getForLanguageCodeAndMessageLocalizer(
			$context->getLanguage()->getCode(),
			$context
		);
	}

}
