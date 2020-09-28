<?php
declare( strict_types=1 );
namespace Wikibase\Lexeme\Presentation\View\Template;

/**
 * @license GPL-2.0-or-later
 */
class VueTemplates {

	public const BASE_PATH = '/../../../resources/templates/';

	// lexeme view
	public const LEMMA = self::BASE_PATH . 'lemma.vue.html';
	public const LEMMA_HEADER = self::BASE_PATH . 'lexemeHeader.vue.html';
	public const CATEGORY_WIDGET = self::BASE_PATH . 'languageAndLexicalCategoryWidget.vue.html';

	// senses
	public const GLOSS_WIDGET = self::BASE_PATH . 'glossWidget.vue.html';

	// forms
	public const REPRESENTATIONS = self::BASE_PATH . 'representations.vue.html';
}
