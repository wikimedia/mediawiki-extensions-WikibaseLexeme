<?php

/**
 * Definition of data types for use with Wikibase.
 * The array returned by the code below is supposed to be merged into $wgWBRepoDataTypes.
 * It defines the formatters used by the repo to display data values of different types.
 *
 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
 * objects or loading classes here!
 *
 * @note: 'validator-factory-callback' fields delegate to a global instance of
 * ValidatorsBuilders
 *
 * @see ValidatorsBuilders
 * @see docs/datatypes.wiki in the Wikibase.git repository for documentation
 *
 * @license GPL-2.0+
 */

use ValueFormatters\FormatterOptions;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\PropertyType\FormIdFormatter;
use Wikibase\Lexeme\PropertyType\LexemeIdHtmlFormatter;
use Wikibase\Lexeme\PropertyType\SenseIdFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\WikibaseRepo;

return [
	'PT:wikibase-lexeme' => [
		'expert-module' => 'wikibase.experts.Lexeme',
		'validator-factory-callback' => function() {
			$factory = WikibaseRepo::getDefaultValidatorBuilders();
			return $factory->getEntityValidators( Lexeme::ENTITY_TYPE );
		},
		'formatter-factory-callback' => function ( $format, FormatterOptions $options ) {
			if ( $format === SnakFormatter::FORMAT_HTML ) {
				return new LexemeIdHtmlFormatter(
					WikibaseRepo::getDefaultInstance()->getEntityLookup(),
					WikibaseRepo::getDefaultInstance()->getEntityTitleLookup(),
					Language::factory( $options->getOption( 'lang' ) )
				);
			}

			return WikibaseRepo::getDefaultValueFormatterBuilders()
				->newEntityIdFormatter( $format, $options );
		},
		'value-type' => 'wikibase-entityid',
	],
	'PT:wikibase-lexeme-form' => [
		'expert-module' => 'wikibase.experts.Form',
		'validator-factory-callback' => function() {
			return [];
		},
		'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
			return new FormIdFormatter();
		},
		'value-type' => 'string',
	],
	'PT:wikibase-lexeme-sense' => [
		'expert-module' => 'wikibase.experts.Sense',
		'validator-factory-callback' => function() {
			return [];
		},
		'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
			return new SenseIdFormatter();
		},
		'value-type' => 'string',
	],
];
