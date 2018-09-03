<?php
/**
 * Aliases for special pages of the WikibaseLexeme extension
 *
 * @file
 * @ingroup Extensions
 */

use Wikibase\Lexeme\Specials\SpecialNewLexeme;

$specialPageAliases = [];
/** English (English) */
$specialPageAliases['en'] = [
	SpecialNewLexeme::PAGE_NAME => [ 'NewLexeme' ],
	'MergeLexemes' => [ 'MergeLexemes' ],
];
