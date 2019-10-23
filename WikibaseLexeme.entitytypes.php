<?php
/**
 * Definition of the lexeme entity type.
 * The array returned by the code below is supposed to be merged into the Repo entity types.
 *
 * @note: Keep in sync with Wikibase
 *
 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
 * objects or loading classes here!
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */

use Wikibase\DataAccess\NullPrefetchingTermLookup;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\SerializableEntityId;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\Lexeme\DataAccess\Store\FormRevisionLookup;
use Wikibase\Lexeme\DataAccess\Store\FormStore;
use Wikibase\Lexeme\DataAccess\Store\FormTitleStoreLookup;
use Wikibase\Lexeme\DataAccess\Store\SenseRevisionLookup;
use Wikibase\Lexeme\DataAccess\Store\SenseStore;
use Wikibase\Lexeme\DataAccess\Store\SenseTitleStoreLookup;
use Wikibase\Lexeme\Domain\Diff\FormDiffer;
use Wikibase\Lexeme\Domain\Diff\FormPatcher;
use Wikibase\Lexeme\Domain\Diff\LexemeDiffer;
use Wikibase\Lexeme\Domain\Diff\LexemePatcher;
use Wikibase\Lexeme\Domain\Diff\SenseDiffer;
use Wikibase\Lexeme\Domain\Diff\SensePatcher;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Serialization\ExternalLexemeSerializer;
use Wikibase\Lexeme\Serialization\FormSerializer;
use Wikibase\Lexeme\Serialization\LexemeDeserializer;
use Wikibase\Lexeme\Serialization\SenseSerializer;
use Wikibase\Lexeme\Serialization\StorageLexemeSerializer;
use Wikibase\Lib\EntityTypeDefinitions as Def;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\TitleLookupBasedEntityArticleIdLookup;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\WikibaseRepo;

return [
	'lexeme' => [
		Def::ARTICLE_ID_LOOKUP_CALLBACK => static function () {
			return new TitleLookupBasedEntityArticleIdLookup(
				WikibaseRepo::getEntityTitleLookup()
			);
		},
		Def::SERIALIZER_FACTORY_CALLBACK => static function ( SerializerFactory $serializerFactory ) {
			return new ExternalLexemeSerializer(
				new StorageLexemeSerializer(
					$serializerFactory->newTermListSerializer(),
					$serializerFactory->newStatementListSerializer()
				)
			);
		},
		Def::DESERIALIZER_FACTORY_CALLBACK => static function ( DeserializerFactory $deserializerFactory ) {
			return new LexemeDeserializer(
				$deserializerFactory->newEntityIdDeserializer(),
				$deserializerFactory->newStatementListDeserializer()
			);
		},

		Def::ENTITY_ID_PATTERN => LexemeId::PATTERN,
		Def::ENTITY_ID_BUILDER => static function ( $serialization ) {
			return new LexemeId( $serialization );
		},
		Def::ENTITY_ID_COMPOSER_CALLBACK => static function ( $repositoryName, $uniquePart ) {
			return new LexemeId( SerializableEntityId::joinSerialization( [
				$repositoryName,
				'',
				'L' . $uniquePart
			] ) );
		},
		Def::ENTITY_DIFFER_STRATEGY_BUILDER => static function () {
			return new LexemeDiffer();
		},
		Def::ENTITY_PATCHER_STRATEGY_BUILDER => static function () {
			return new LexemePatcher();
		},

		// Identifier of a resource loader module that, when `require`d, returns a function
		// returning a deserializer
		Def::JS_DESERIALIZER_FACTORY_FUNCTION => 'wikibase.lexeme.getDeserializer',
		Def::SUB_ENTITY_TYPES => [
			'form',
			'sense',
		],
		Def::LUA_ENTITY_MODULE => 'mw.wikibase.lexeme.entity.lexeme',
		Def::PREFETCHING_TERM_LOOKUP_CALLBACK => static function () {
			return new NullPrefetchingTermLookup();
		},
	],
	'form' => [
		Def::ARTICLE_ID_LOOKUP_CALLBACK => static function () {
			return new TitleLookupBasedEntityArticleIdLookup(
				WikibaseRepo::getEntityTitleLookup()
			);
		},
		Def::ENTITY_STORE_FACTORY_CALLBACK => static function (
			EntityStore $defaultStore,
			EntityRevisionLookup $lookup
		) {
			return new FormStore( $defaultStore, $lookup );
		},
		Def::ENTITY_REVISION_LOOKUP_FACTORY_CALLBACK => static function (
			EntityRevisionLookup $defaultLookup
		) {
			return new FormRevisionLookup( $defaultLookup );
		},
		Def::ENTITY_TITLE_STORE_LOOKUP_FACTORY_CALLBACK => static function (
			EntityTitleStoreLookup $defaultLookup
		) {
			return new FormTitleStoreLookup( $defaultLookup );
		},
		Def::ENTITY_ID_PATTERN => FormId::PATTERN,
		Def::ENTITY_ID_BUILDER => static function ( $serialization ) {
			return new FormId( $serialization );
		},
		Def::ENTITY_DIFFER_STRATEGY_BUILDER => static function () {
			return new FormDiffer();
		},
		Def::ENTITY_PATCHER_STRATEGY_BUILDER => static function () {
			return new FormPatcher();
		},
		Def::SERIALIZER_FACTORY_CALLBACK => static function ( SerializerFactory $serializerFactory ) {
			return new FormSerializer(
				$serializerFactory->newTermListSerializer(),
				$serializerFactory->newStatementListSerializer()
			);
		},
		Def::PREFETCHING_TERM_LOOKUP_CALLBACK => static function () {
			return new NullPrefetchingTermLookup();
		},
		DEF::LUA_ENTITY_MODULE => 'mw.wikibase.lexeme.entity.form',
	],
	'sense' => [
		Def::ARTICLE_ID_LOOKUP_CALLBACK => static function () {
			return new TitleLookupBasedEntityArticleIdLookup(
				WikibaseRepo::getEntityTitleLookup()
			);
		},
		Def::ENTITY_STORE_FACTORY_CALLBACK => static function (
			EntityStore $defaultStore,
			EntityRevisionLookup $lookup
		) {
			return new SenseStore( $defaultStore, $lookup );
		},
		Def::ENTITY_REVISION_LOOKUP_FACTORY_CALLBACK => static function (
			EntityRevisionLookup $defaultLookup
		) {
			return new SenseRevisionLookup( $defaultLookup );
		},
		Def::ENTITY_TITLE_STORE_LOOKUP_FACTORY_CALLBACK => static function (
			EntityTitleStoreLookup $defaultLookup
		) {
			return new SenseTitleStoreLookup( $defaultLookup );
		},
		Def::ENTITY_ID_PATTERN => SenseId::PATTERN,
		Def::ENTITY_ID_BUILDER => static function ( $serialization ) {
			return new SenseId( $serialization );
		},
		Def::ENTITY_DIFFER_STRATEGY_BUILDER => static function () {
			return new SenseDiffer();
		},
		Def::ENTITY_PATCHER_STRATEGY_BUILDER => static function () {
			return new SensePatcher();
		},
		Def::SERIALIZER_FACTORY_CALLBACK => static function ( SerializerFactory $serializerFactory ) {
			return new SenseSerializer(
				$serializerFactory->newTermListSerializer(),
				$serializerFactory->newStatementListSerializer()
			);
		},
		Def::PREFETCHING_TERM_LOOKUP_CALLBACK => static function () {
			return new NullPrefetchingTermLookup();
		},
		DEF::LUA_ENTITY_MODULE => 'mw.wikibase.lexeme.entity.sense',
	]
];
