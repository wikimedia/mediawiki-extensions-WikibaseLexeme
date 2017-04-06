<?php

namespace Wikibase\Lexeme;

/**
 * Contains templates of lexemes commonly used in server-side output generation and client-side
 * JavaScript processing.
 *
 * @license GPL-2.0+
 *
 * @return array
 */

return call_user_func( function() {
	$templates = [];

	$templates['wikibase-lexeme-form'] = <<<'HTML'
<h3 class="wikibase-lexeme-form-representation" lang="$1">$2<!-- wikibase-lexeme-form-id --> $3</h3>
HTML;

	//TODO Join these templates
	$templates['wikibase-lexeme-form-id'] = <<<'HTML'
<span class="wikibase-lexeme-form-id wikibase-title-id">$1</span>
HTML;

	return $templates;
} );
