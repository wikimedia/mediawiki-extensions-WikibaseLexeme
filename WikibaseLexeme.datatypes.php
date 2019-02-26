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
 * @license GPL-2.0-or-later
 */

use ValueFormatters\FormatterOptions;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Presentation\Formatters\FormIdHtmlFormatter;
use Wikibase\Lexeme\Presentation\Formatters\FormIdTextFormatter;
use Wikibase\Lexeme\Presentation\Formatters\LexemeIdHtmlFormatter;
use Wikibase\Lexeme\Presentation\Formatters\RedirectedLexemeSubEntityIdHtmlFormatter;
use Wikibase\Lexeme\Presentation\Formatters\SenseIdHtmlFormatter;
use Wikibase\Lexeme\Presentation\Formatters\SenseIdTextFormatter;
use Wikibase\Lib\EntityIdValueFormatter;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\SnakFormat;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\WikibaseRepo;

return [
	'PT:wikibase-lexeme' => [
		'expert-module' => 'wikibase.experts.Lexeme',
		'validator-factory-callback' => function() {
			$factory = WikibaseRepo::getDefaultValidatorBuilders();
			return $factory->getEntityValidators( Lexeme::ENTITY_TYPE );
		},
		'formatter-factory-callback' => function ( $format, FormatterOptions $options ) {
			$snakFormat = new SnakFormat();

			if ( $snakFormat->getBaseFormat( $format ) === SnakFormatter::FORMAT_HTML ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$userLanguage = $wikibaseRepo->getUserLanguage();

				// TODO: Use LanguageFallbackLabelDescriptionLookupFactory instead?
				$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
					$wikibaseRepo->getTermLookup(),
					$wikibaseRepo->getLanguageFallbackChainFactory()
						->newFromLanguage( $userLanguage )
				);

				return new EntityIdValueFormatter(
					new LexemeIdHtmlFormatter(
						$wikibaseRepo->getEntityLookup(),
						$labelDescriptionLookup,
						$wikibaseRepo->getEntityTitleLookup(),
						new MediaWikiLocalizedTextProvider( $userLanguage )
					)
				);
			}

			return WikibaseRepo::getDefaultValueFormatterBuilders()
				->newEntityIdFormatter( $format, $options );
		},
		'value-type' => 'wikibase-entityid',
	],
	'PT:wikibase-form' => [
		'expert-module' => 'wikibase.experts.Form',
		'validator-factory-callback' => function() {
			$factory = WikibaseRepo::getDefaultValidatorBuilders();
			return $factory->getEntityValidators( Form::ENTITY_TYPE );
		},
		'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$userLanguage = $wikibaseRepo->getUserLanguage();
			$revisionLookup = $wikibaseRepo->getEntityRevisionLookup();
			$textProvider = new MediaWikiLocalizedTextProvider( $userLanguage );
			$snakFormat = new SnakFormat();

			if ( $snakFormat->getBaseFormat( $format ) === SnakFormatter::FORMAT_HTML ) {
				$titleLookup = $wikibaseRepo->getEntityTitleLookup();
				$languageLabelLookupFactory = $wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory();
				$languageLabelLookup = $languageLabelLookupFactory->newLabelDescriptionLookup( $userLanguage );
				$baseFormatter = new FormIdHtmlFormatter(
					$revisionLookup,
					$languageLabelLookup,
					$titleLookup,
					$textProvider,
					new RedirectedLexemeSubEntityIdHtmlFormatter( $titleLookup )
				);
			} else {
				$baseFormatter = new FormIdTextFormatter(
					$revisionLookup,
					$textProvider
				);
			}
			return new EntityIdValueFormatter(
				$baseFormatter
			);
		},
		'value-type' => 'wikibase-entityid',
	],
	'PT:wikibase-sense' => [
		'expert-module' => 'wikibase.experts.Sense',
		'validator-factory-callback' => function() {
			$factory = WikibaseRepo::getDefaultValidatorBuilders();
			return $factory->getEntityValidators( Sense::ENTITY_TYPE );
		},
		'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$revisionLookup = $wikibaseRepo->getEntityRevisionLookup();
			$language = $wikibaseRepo->getUserLanguage();

			$localizedTextProvider = new MediaWikiLocalizedTextProvider( $language );

			$languageFallbackChainFactory = $wikibaseRepo->getLanguageFallbackChainFactory();
			$fallbackChain = $languageFallbackChainFactory->newFromLanguage( $language );
			$snakFormat = new SnakFormat();

			if ( $snakFormat->getBaseFormat( $format ) === SnakFormatter::FORMAT_HTML ) {
				$titleLookup = $wikibaseRepo->getEntityTitleLookup();

				return new EntityIdValueFormatter(
					new SenseIdHtmlFormatter(
						$titleLookup,
						$revisionLookup,
						$localizedTextProvider,
						$fallbackChain,
						new LanguageFallbackIndicator( $wikibaseRepo->getLanguageNameLookup() )
					)
				);
			}

			return new EntityIdValueFormatter(
					new SenseIdTextFormatter(
					$revisionLookup,
					$localizedTextProvider
				)
			);
		},
		'value-type' => 'wikibase-entityid',
	],
];
