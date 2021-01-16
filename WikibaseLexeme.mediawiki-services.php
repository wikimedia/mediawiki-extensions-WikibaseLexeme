<?php

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookup;
use Wikibase\Lexeme\MediaWiki\Content\LexemeTermLanguages;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\Validators\EntityExistsValidator;
use Wikibase\Repo\WikibaseRepo;

// TODO Replace by framework-agnostic DI container.
// Pimple e.g. is well known in the free world and yet part of mediawiki-vendor
// Challenge: Dedicated API endpoints (e.g. AddForm) need to have it passed w/o singletons/globals
return call_user_func( function () {
	// TODO Problem when removing a code after such an item exists in DB
	$additionalLanguages = [
		'az-cyrl', // T265906
		'ctg', // T271589
		'de-1901', // T250559
		'eo-hsistemo', // T257422
		'eo-xsistemo', // T257422
		'frm', // T268332
		'fro', // T268332
		'ja-hira', // T262330
		'ja-kana', // T262330
		'ja-hrkt', // T262330
		'lij-mc', // T254968
		'mis',
		'non', // T265782
		'non-runr', // T265782
		'nrf-gg', // T223716
		'nrf-je', // T223716
		'rkt', // T271589
		'rm-rumgr', // T210293
		'rm-surmiran', // T210293
		'rm-sutsilv', // T210293
		'rm-sursilv', // T210293
		'rm-vallader', // T210293
		'rm-puter', // T210293
		'sat-latn', // T262967
		'sat-beng', // T262967
		'sat-orya' // T262967
	];

	return [
		'WikibaseLexemeTermLanguages' =>
			function ( MediaWikiServices $mediawikiServices ) use ( $additionalLanguages ) {
				return new LexemeTermLanguages(
					$additionalLanguages
				);
			},
		'WikibaseLexemeLanguageNameLookup' =>
			function ( MediaWikiServices $mediawikiServices ) use ( $additionalLanguages ) {
				return new LexemeLanguageNameLookup(
					RequestContext::getMain(),
					$additionalLanguages,
					WikibaseRepo::getDefaultInstance()->getLanguageNameLookup()
				);
			},
		'WikibaseLexemeEditFormChangeOpDeserializer' => function (
			MediaWikiServices $mediaWikiServices
		) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			return new EditFormChangeOpDeserializer(
				new RepresentationsChangeOpDeserializer(
					new TermDeserializer(),
					$wikibaseRepo->getStringNormalizer(),
					new LexemeTermSerializationValidator(
						new LexemeTermLanguageValidator( WikibaseLexemeServices::getTermLanguages() )
					)
				),
				new ItemIdListDeserializer( new ItemIdParser() ),
				new ClaimsChangeOpDeserializer(
					$wikibaseRepo->getExternalFormatStatementDeserializer(),
					$wikibaseRepo->getChangeOpFactoryProvider()->getStatementChangeOpFactory()
				),
				new EntityExistsValidator( $wikibaseRepo->getEntityLookup(), 'item' )
			);
		},
	];
} );
