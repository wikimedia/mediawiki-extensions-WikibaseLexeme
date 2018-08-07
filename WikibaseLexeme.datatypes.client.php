<?php

use ValueFormatters\FormatterOptions;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lexeme\PropertyType\SenseIdFormatter;

return [
	'PT:wikibase-lexeme' => [
		'formatter-factory-callback' => function ( $format, FormatterOptions $options ) {
			return WikibaseClient::getDefaultValueFormatterBuilders()
				->newEntityIdFormatter( $format, $options );
		},
		'value-type' => 'wikibase-entityid',
	],
	'PT:wikibase-form' => [
		'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
			return WikibaseClient::getDefaultValueFormatterBuilders()
				->newEntityIdFormatter( $format, $options );
		},
		'value-type' => 'wikibase-entityid',
	],
	'PT:wikibase-sense' => [
		'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
			return new SenseIdFormatter();
		},
		'value-type' => 'wikibase-entityid',
	],
];
