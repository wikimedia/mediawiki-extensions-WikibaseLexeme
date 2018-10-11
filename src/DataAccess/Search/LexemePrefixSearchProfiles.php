<?php
/**
 * Profile defining weights for query matching options for Lexemes.
 * exact - exact match
 * folded - casefolded/asciifolded match
 * prefix - prefix match
 * form-discount - discount for matching form and not lemma
 * space-discount - how much we discount the match for matching without trailing space
 */
return [
// FIXME: no tuning yet
'lexeme_prefix' => [
	'exact' => 2,
	'folded' => 1.5,
	'prefix' => 1,
	'space-discount' => 0.8,
	'form-discount' => 1,
],
];
