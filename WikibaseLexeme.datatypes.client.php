<?php

use ValueFormatters\FormatterOptions;
use Wikibase\Client\WikibaseClient;

return [
	'PT:wikibase-lexeme' => [
		'formatter-factory-callback' => static function ( $format, FormatterOptions $options ) {
			return WikibaseClient::getDefaultValueFormatterBuilders()
				->newEntityIdFormatter( $format, $options );
		},
		'value-type' => 'wikibase-entityid',
	],
	'PT:wikibase-form' => [
		'formatter-factory-callback' => static function ( $format, FormatterOptions $options ) {
			return WikibaseClient::getDefaultValueFormatterBuilders()
				->newEntityIdFormatter( $format, $options );
		},
		'value-type' => 'wikibase-entityid',
	],
	'PT:wikibase-sense' => [
		'formatter-factory-callback' => static function ( $format, FormatterOptions $options ) {
			return WikibaseClient::getDefaultValueFormatterBuilders()
				->newEntityIdFormatter( $format, $options );
		},
		'value-type' => 'wikibase-entityid',
	],
];
