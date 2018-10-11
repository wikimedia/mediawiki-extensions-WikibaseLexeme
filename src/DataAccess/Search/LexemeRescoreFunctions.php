<?php
return [
	'lexeme_weight' => [
		'score_mode' => 'sum',
		'functions' => [
			[
				// Incoming links: k = 100, since it is normal to have a bunch of incoming links
				'type' => 'satu',
				'weight' => '0.4',
				'params' => [ 'field' => 'incoming_links', 'missing' => 0, 'a' => 1, 'k' => 100 ]
			],
			[
				// Statement count: k = 20, tens of statements is a lot
				'type' => 'satu',
				'weight' => '0.6',
				'params' => [ 'field' => 'statement_count', 'missing' => 0, 'a' => 2, 'k' => 20 ]
			],
		],
	],
];
