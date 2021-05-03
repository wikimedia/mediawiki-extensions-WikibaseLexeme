<?php

namespace Wikibase\Lexeme;

/**
 * Contains templates of lexemes commonly used in server-side output generation and client-side
 * JavaScript processing.
 *
 * @license GPL-2.0-or-later
 *
 * @return array
 */

return call_user_func( static function () {
	$templates = [];

	$templates['wikibase-lexeme-form'] = <<<'HTML'
<div class="wikibase-lexeme-form" id="$5">
	<div class="wikibase-lexeme-form-header">
		<div class="wikibase-lexeme-form-id">$1</div>
		<div class="form-representations">$2</div>
	</div>
	<div class="wikibase-lexeme-form-body">
		$3
		$4
	</div>
</div>
HTML;

	$templates['wikibase-lexeme-form-grammatical-features'] = <<<'HTML'
<div class="wikibase-lexeme-form-grammatical-features">
		<label class="wikibase-lexeme-form-grammatical-features-header">$1</label>
		<div class="wikibase-lexeme-form-grammatical-features-values">$2</div>
</div>
HTML;

	$templates['wikibase-lexeme-sense'] = <<< 'HTML'
<div class="wikibase-lexeme-sense" data-sense-id="$5" id="$4">
	<div class="wikibase-lexeme-sense-header">
		<div class="wikibase-lexeme-sense-id">$1</div>
		$2
	</div>
	<div class="wikibase-lexeme-sense-statements">
		$3
	</div>
</div>
HTML;

	return $templates;
} );
