<?php

namespace Wikibase\Lexeme\MediaWiki\Content;

use MediaWiki\Languages\LanguageNameUtils;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\MediaWikiContentLanguages;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\UnionContentLanguages;

/**
 * @license GPL-2.0-or-later
 */
class LexemeTermLanguages implements ContentLanguages {

	/**
	 * @var ContentLanguages
	 */
	private $contentLanguages;

	/**
	 * @param string[] $additionalLanguageCodes Codes beyond the Mediawiki's Language ones
	 * @param LanguageNameUtils|null $languageNameUtils
	 */
	public function __construct(
		array $additionalLanguageCodes,
		LanguageNameUtils $languageNameUtils = null
	) {
		$this->contentLanguages = new UnionContentLanguages(
			new MediaWikiContentLanguages( $languageNameUtils ),
			new StaticContentLanguages( $additionalLanguageCodes )
		);
	}

	/**
	 * @return string[] Array of language codes supported as content language
	 */
	public function getLanguages() {
		return $this->contentLanguages->getLanguages();
	}

	/**
	 * @param string $languageCode
	 *
	 * @return bool
	 */
	public function hasLanguage( $languageCode ) {
		return $this->contentLanguages->hasLanguage( $languageCode );
	}

}
