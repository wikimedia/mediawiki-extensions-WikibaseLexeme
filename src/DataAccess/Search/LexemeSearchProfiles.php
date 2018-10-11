<?php
// Search profiles for fulltext search
// Matches the syntax of Cirrus search profiles, e.g. in FullTextQueryBuilderProfiles.config.php
// Note that these will be merged with Cirrus standard profiles,
// so prefixing with 'wikibase' is recommended.

use Wikibase\Lexeme\DataAccess\Search\LexemeFullTextQueryBuilder;

return [
// FIXME: no tuning yet
	'lexeme_fulltext' => [
		'builder_class' => LexemeFullTextQueryBuilder::class,
		'settings' => [
			'any'          => 0.1,
			'exact'        => 2,
			'folded'       => 1.5,
			'partial'      => 1,
			'form-discount' => 1,
		]
	],
];
