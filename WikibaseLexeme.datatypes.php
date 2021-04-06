<?php

/**
 * Definition of data types for use with Wikibase.
 * The array returned by the code below is supposed to be merged into the Repo data types.
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

use MediaWiki\MediaWikiServices;
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
use Wikibase\Lib\Formatters\EntityIdValueFormatter;
use Wikibase\Lib\Formatters\SnakFormat;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\WikibaseRepo;

return [
	'PT:wikibase-lexeme' => [
		'expert-module' => 'wikibase.experts.Lexeme',
		'validator-factory-callback' => function () {
			$factory = WikibaseRepo::getDefaultValidatorBuilders();
			return $factory->getEntityValidators( Lexeme::ENTITY_TYPE );
		},
		'formatter-factory-callback' => function ( $format, FormatterOptions $options ) {
			$snakFormat = new SnakFormat();

			if ( $snakFormat->getBaseFormat( $format ) === SnakFormatter::FORMAT_HTML ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$userLanguage = WikibaseRepo::getUserLanguage();

				// TODO: Use LanguageFallbackLabelDescriptionLookupFactory instead?
				$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
					WikibaseRepo::getTermLookup(),
					WikibaseRepo::getLanguageFallbackChainFactory()
						->newFromLanguage( $userLanguage )
				);

				return new EntityIdValueFormatter(
					new LexemeIdHtmlFormatter(
						$wikibaseRepo->getEntityLookup(),
						$labelDescriptionLookup,
						WikibaseRepo::getEntityTitleLookup(),
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
		'validator-factory-callback' => function () {
			$factory = WikibaseRepo::getDefaultValidatorBuilders();
			return $factory->getEntityValidators( Form::ENTITY_TYPE );
		},
		'formatter-factory-callback' => function ( $format, FormatterOptions $options ) {
			$mwServices = MediaWikiServices::getInstance();
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$userLanguage = WikibaseRepo::getUserLanguage( $mwServices );
			$revisionLookup = $wikibaseRepo->getEntityRevisionLookup();
			$textProvider = new MediaWikiLocalizedTextProvider( $userLanguage );
			$snakFormat = new SnakFormat();

			if ( $snakFormat->getBaseFormat( $format ) === SnakFormatter::FORMAT_HTML ) {
				$titleLookup = WikibaseRepo::getEntityTitleLookup( $mwServices );
				$languageLabelLookupFactory = WikibaseRepo::getLanguageFallbackLabelDescriptionLookupFactory(
					$mwServices );
				$languageLabelLookup = $languageLabelLookupFactory->newLabelDescriptionLookup( $userLanguage );
				$baseFormatter = new FormIdHtmlFormatter(
					$revisionLookup,
					$languageLabelLookup,
					$titleLookup,
					$textProvider,
					new RedirectedLexemeSubEntityIdHtmlFormatter( $titleLookup ),
					$mwServices->getLanguageFactory()
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
		'validator-factory-callback' => function () {
			$factory = WikibaseRepo::getDefaultValidatorBuilders();
			return $factory->getEntityValidators( Sense::ENTITY_TYPE );
		},
		'formatter-factory-callback' => function ( $format, FormatterOptions $options ) {
			$mwServices = MediaWikiServices::getInstance();
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$revisionLookup = $wikibaseRepo->getEntityRevisionLookup();
			$language = WikibaseRepo::getUserLanguage( $mwServices );

			$localizedTextProvider = new MediaWikiLocalizedTextProvider( $language );

			$languageFallbackChainFactory = WikibaseRepo::getLanguageFallbackChainFactory();
			$fallbackChain = $languageFallbackChainFactory->newFromLanguage( $language );
			$snakFormat = new SnakFormat();

			if ( $snakFormat->getBaseFormat( $format ) === SnakFormatter::FORMAT_HTML ) {
				$titleLookup = WikibaseRepo::getEntityTitleLookup( $mwServices );

				return new EntityIdValueFormatter(
					new SenseIdHtmlFormatter(
						$titleLookup,
						$revisionLookup,
						$localizedTextProvider,
						$fallbackChain,
						new LanguageFallbackIndicator( $wikibaseRepo->getLanguageNameLookup() ),
						$mwServices->getLanguageFactory()
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
