<?php
return [
	'lexeme_prefix' => [
		'i18n_msg' => 'wikibaselexeme-rescore-profile-prefix',
		'supported_namespaces' => 'all',
		'rescore' => [
			[
				'window' => 8192,
				'window_size_override' => 'EntitySearchRescoreWindowSize',
				'query_weight' => 1.0,
				'rescore_query_weight' => 1.0,
				'score_mode' => 'total',
				'type' => 'function_score',
				'function_chain' => 'lexeme_weight'
			],
		]
	],
];
