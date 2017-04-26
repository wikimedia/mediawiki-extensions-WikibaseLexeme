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
<div class="wikibase-lexeme-form">
	<h3 class="wikibase-lexeme-form-representation" lang="$1">$2 $3</h3>
	<div class="wikibase-lexeme-form-grammatical-features">
		<div class="wikibase-lexeme-form-grammatical-features-header">Grammatical features</div>
		<div class="wikibase-lexeme-form-grammatical-features-values">$4</div>
	</div>
</div>
HTML;

	$templates['wikibase-lexeme-form-id'] = <<<'HTML'
<span class="wikibase-lexeme-form-id wikibase-title-id">$1</span>
HTML;

	return $templates;
} );
