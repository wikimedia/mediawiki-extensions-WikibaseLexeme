<?php
/**
 * Definition of the lexeme entity type.
 * The array returned by the code below is supposed to be merged into $wgWBRepoEntityTypes.
 *
 * @note: Keep in sync with Wikibase
 *
 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
 * objects or loading classes here!
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */

use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lexeme\DataModel\Serialization\ExternalLexemeSerializer;
use Wikibase\Lexeme\DataModel\Serialization\FormSerializer;
use Wikibase\Lexeme\DataModel\Serialization\LexemeDeserializer;
use Wikibase\Lexeme\DataModel\Serialization\SenseSerializer;
use Wikibase\Lexeme\DataModel\Serialization\StorageLexemeSerializer;
use Wikibase\Lexeme\DataModel\Services\Diff\FormDiffer;
use Wikibase\Lexeme\DataModel\Services\Diff\FormPatcher;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiffer;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemePatcher;
use Wikibase\Lexeme\Store\FormRevisionLookup;
use Wikibase\Lexeme\Store\FormStore;
use Wikibase\Lexeme\Store\FormTitleStoreLookup;
use Wikibase\Lexeme\Store\SenseRevisionLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Store\EntityTitleStoreLookup;

return [
	'lexeme' => [
		'serializer-factory-callback' => function ( SerializerFactory $serializerFactory ) {
			return new ExternalLexemeSerializer(
				new StorageLexemeSerializer(
					$serializerFactory->newTermListSerializer(),
					$serializerFactory->newStatementListSerializer()
				)
			);
		},
		'deserializer-factory-callback' => function ( DeserializerFactory $deserializerFactory ) {
			return new LexemeDeserializer(
				$deserializerFactory->newEntityIdDeserializer(),
				$deserializerFactory->newStatementListDeserializer()
			);
		},

		'entity-id-pattern' => LexemeId::PATTERN,
		'entity-id-builder' => function ( $serialization ) {
			return new LexemeId( $serialization );
		},
		'entity-id-composer-callback' => function ( $repositoryName, $uniquePart ) {
			return new LexemeId( EntityId::joinSerialization( [
				$repositoryName,
				'',
				'L' . $uniquePart
			] ) );
		},
		'entity-differ-strategy-builder' => function () {
			return new LexemeDiffer();
		},
		'entity-patcher-strategy-builder' => function () {
			return new LexemePatcher();
		},

		// Identifier of a resource loader module that, when `require`d, returns a function
		// returning a deserializer
		'js-deserializer-factory-function' => 'wikibase.lexeme.getDeserializer',
		'sub-entity-types' => [
			'form'
		],
	],
	'form' => [
		'entity-store-factory-callback' => function (
			EntityStore $defaultStore,
			EntityRevisionLookup $lookup
		) {
			return new FormStore( $defaultStore, $lookup );
		},
		'entity-revision-lookup-factory-callback' => function (
			EntityRevisionLookup $defaultLookup
		) {
			return new FormRevisionLookup( $defaultLookup );
		},
		'entity-title-store-lookup-factory-callback' => function (
			EntityTitleStoreLookup $defaultLookup
		) {
			return new FormTitleStoreLookup( $defaultLookup );
		},
		'entity-id-pattern' => FormId::PATTERN,
		'entity-id-builder' => function ( $serialization ) {
			return new FormId( $serialization );
		},
		'entity-differ-strategy-builder' => function () {
			return new FormDiffer();
		},
		'entity-patcher-strategy-builder' => function () {
			return new FormPatcher();
		},
		'serializer-factory-callback' => function ( SerializerFactory $serializerFactory ) {
			return new FormSerializer(
				$serializerFactory->newTermListSerializer(),
				$serializerFactory->newStatementListSerializer()
			);
		},
	],
	'sense' => [
		'entity-revision-lookup-factory-callback' => function (
			EntityRevisionLookup $defaultLookup
		) {
			return new SenseRevisionLookup( $defaultLookup );
		},
		'entity-id-pattern' => SenseId::PATTERN,
		'entity-id-builder' => function ( $serialization ) {
			return new SenseId( $serialization );
		},
		'serializer-factory-callback' => function ( SerializerFactory $serializerFactory ) {
			return new SenseSerializer(
				$serializerFactory->newTermListSerializer(),
				$serializerFactory->newStatementListSerializer()
			);
		}
	]
];
