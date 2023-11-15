<?php

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\DataAccess\Store\EntityLookupLemmaLookup;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookupFactory;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\CachingItemOrderProvider;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\WikiPageItemOrderProvider;
use Wikibase\Lib\UnionContentLanguages;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Validators\EntityExistsValidator;
use Wikibase\Repo\WikibaseRepo;

// TODO Replace by framework-agnostic DI container.
// Pimple e.g. is well known in the free world and yet part of mediawiki-vendor
// Challenge: Dedicated API endpoints (e.g. AddForm) need to have it passed w/o singletons/globals
return call_user_func( static function () {
	// TODO Problem when removing a code after such a Lexeme exists in DB?!
	// These are not supported by Wikibase for monolingual text and some should probably not be supported here either,
	// but keep these for backwards compatibility.
	$additionalTermLanguages = [
		'bat-smg',
		'be-x-old',
		'ccp-beng', // T272442
		'de-formal',
		'eo-hsistemo', // T257422
		'eo-xsistemo', // T257422
		'es-formal',
		'fiu-vro',
		'ha-arab', // T282512
		'hu-formal',
		'lad-hebr', // T308794
		'nl-informal',
		'nn-hognorsk', // T235344
		'pt-ao1990', // T270043
		'pt-colb1945', // T270043
		'rhg-rohg', // T272442
		'roa-rup',
		'sat-latn', // T262967
		'sat-beng', // T262967
		'sat-orya', // T262967
		'simple',
		'sux-latn', // T282512
		'sux-xsux', // T282512
		'syl-beng', // T267480
		'zh-classical',
		'zh-min-nan',
		'zh-yue',
	];

	$additionalLocalizedLanguages = array_merge( $additionalTermLanguages, [
		// Languages that are supported in Wikibase (via cldr) but localized here in LexemeLanguageNameLookup.
		// These should be localized via the cldr extension (T352922).
		'apc',
		'az-cyrl',
		'bas',
		'bfi',
		'bzs',
		'cak',
		'ccp',
		'cnh',
		'ctg',
		'de-1901',
		'enm',
		'fon',
		'frm',
		'fro',
		'gmh',
		'goh',
		'gsg',
		'hoc',
		'ja-hira',
		'ja-hrkt',
		'ja-kana',
		'lij-mc',
		'mis',
		'mvf',
		'nd',
		'non',
		'non-runr',
		'nr',
		'nrf-gg',
		'nrf-je',
		'obt',
		'pks',
		'quc',
		'rah',
		'rkt',
		'rm-puter',
		'rm-rumgr',
		'rm-surmiran',
		'rm-sursilv',
		'rm-sutsilv',
		'rm-vallader',
		'sia',
		'sjk',
		'tlh-latn',
		'tlh-piqd',
		'txg',
		'xbm',
	] );

	return [
		'WikibaseLexemeTermLanguages' =>
			static function ( MediaWikiServices $mediawikiServices ) use ( $additionalTermLanguages ) {
				return new UnionContentLanguages(
					new StaticContentLanguages( $additionalTermLanguages ),
					WikibaseContentLanguages::getDefaultMonolingualTextLanguages(
						$mediawikiServices->getLanguageNameUtils()
					)
				);
			},
		'WikibaseLexemeLanguageNameLookupFactory' =>
			static function ( MediaWikiServices $mediawikiServices ) use ( $additionalLocalizedLanguages ) {
				return new LexemeLanguageNameLookupFactory(
					WikibaseRepo::getLanguageNameLookupFactory( $mediawikiServices ),
					$additionalLocalizedLanguages
				);
			},
		'WikibaseLexemeMobileView' =>
			static function ( MediaWikiServices $mediawikiServices ): bool {
				if ( $mediawikiServices->hasService( 'MobileFrontend.Context' ) ) {
						$mobileContext = $mediawikiServices->getService( 'MobileFrontend.Context' );
						return $mobileContext->shouldDisplayMobileView();
				}
				return false;
			},
		'WikibaseLexemeLemmaLookup' =>
			static function ( MediaWikiServices $mediawikiServices ) {
				return new EntityLookupLemmaLookup( WikibaseRepo::getEntityLookup( $mediawikiServices ) );
			},
		'WikibaseLexemeLemmaTermValidator' => static function (
			MediaWikiServices $services
		): LemmaTermValidator {
			// TODO: move to setting
			return new LemmaTermValidator( LemmaTermValidator::LEMMA_MAX_LENGTH );
		},
		'WikibaseLexemeEditFormChangeOpDeserializer' => static function (
			MediaWikiServices $mediaWikiServices
		) {
			$entityLookup = WikibaseRepo::getStore( $mediaWikiServices )->getEntityLookup(
				Store::LOOKUP_CACHING_DISABLED,
				LookupConstants::LATEST_FROM_MASTER
			);
			return new EditFormChangeOpDeserializer(
				new RepresentationsChangeOpDeserializer(
					new TermDeserializer(),
					WikibaseRepo::getStringNormalizer( $mediaWikiServices ),
					new LexemeTermSerializationValidator(
						new LexemeTermLanguageValidator(
							WikibaseLexemeServices::getTermLanguages( $mediaWikiServices )
						)
					)
				),
				new ItemIdListDeserializer( new ItemIdParser() ),
				new ClaimsChangeOpDeserializer(
					WikibaseRepo::getExternalFormatStatementDeserializer( $mediaWikiServices ),
					WikibaseRepo::getChangeOpFactoryProvider( $mediaWikiServices )
						->getStatementChangeOpFactory()
				),
				new EntityExistsValidator( $entityLookup, 'item' )
			);
		},
		'WikibaseLexemeGrammaticalFeaturesOrderProvider' => static function (
			MediaWikiServices $mediaWikiServices
		) {
			$grammaticalFeaturesOrderProvider = new CachingItemOrderProvider(
				new WikiPageItemOrderProvider(
					$mediaWikiServices->getWikiPageFactory(),
					$mediaWikiServices->getTitleFactory()
						->newFromTextThrow( 'MediaWiki:WikibaseLexeme-SortedGrammaticalFeatures' )
				),
				ObjectCache::getLocalClusterInstance(),
				'wikibaseLexeme-grammaticalFeaturesOrderProvider'
			);

			return $grammaticalFeaturesOrderProvider;
		},
	];
} );
