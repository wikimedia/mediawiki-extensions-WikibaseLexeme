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
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */

use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lexeme\ChangeOp\Deserialization\LanguageChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LemmaChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LexemeChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LexicalCategoryChangeOpDeserializer;
use Wikibase\Lexeme\Content\LexemeContent;
use Wikibase\Lexeme\Content\LexemeHandler;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\Serialization\ExternalLexemeSerializer;
use Wikibase\Lexeme\DataModel\Serialization\LexemeDeserializer;
use Wikibase\Lexeme\DataModel\Serialization\StorageLexemeSerializer;
use Wikibase\Lexeme\DataModel\Services\Diff\FormDiffer;
use Wikibase\Lexeme\DataModel\Services\Diff\FormPatcher;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiffer;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemePatcher;
use Wikibase\Lexeme\Diff\LexemeDiffVisualizer;
use Wikibase\Lexeme\Rdf\LexemeRdfBuilder;
use Wikibase\Lexeme\Search\LexemeFieldDefinitions;
use Wikibase\Lexeme\Store\FormRevisionLookup;
use Wikibase\Lexeme\Store\FormStore;
use Wikibase\Lexeme\Store\FormTitleStoreLookup;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Lexeme\View\LexemeViewFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityTermsView;
use Wikimedia\Purtle\RdfWriter;

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
		'storage-serializer-factory-callback' => function ( SerializerFactory $serializerFactory ) {
			return new StorageLexemeSerializer(
				$serializerFactory->newTermListSerializer(),
				$serializerFactory->newStatementListSerializer()
			);
		},
		'deserializer-factory-callback' => function ( DeserializerFactory $deserializerFactory ) {
			return new LexemeDeserializer(
				$deserializerFactory->newEntityIdDeserializer(),
				$deserializerFactory->newStatementListDeserializer()
			);
		},
		'view-factory-callback' => function (
			$languageCode,
			LabelDescriptionLookup $labelDescriptionLookup,
			LanguageFallbackChain $fallbackChain,
			EditSectionGenerator $editSectionGenerator,
			EntityTermsView $entityTermsView
		) {
			$factory = new LexemeViewFactory(
				$languageCode,
				$labelDescriptionLookup,
				$fallbackChain,
				$editSectionGenerator,
				$entityTermsView,
				WikibaseRepo::getDefaultInstance()->getEntityIdHtmlLinkFormatterFactory()
			);

			return $factory->newLexemeView();
		},
		'content-model-id' => LexemeContent::CONTENT_MODEL_ID,
		'content-handler-factory-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			return new LexemeHandler(
				$wikibaseRepo->getStore()->getTermIndex(),
				$wikibaseRepo->getEntityContentDataCodec(),
				$wikibaseRepo->getEntityConstraintProvider(),
				$wikibaseRepo->getValidatorErrorLocalizer(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getEntityIdLookup(),
				$wikibaseRepo->getEntityLookup(),
				$wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory(),
				new LexemeFieldDefinitions()
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
		'entity-factory-callback' => function () {
			return new Lexeme();
		},
		// Identifier of a resource loader module that, when `require`d, returns a function
		// returning a deserializer
		'js-deserializer-factory-function' => 'wikibase.lexeme.getDeserializer',
		'changeop-deserializer-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$lexemeValidatorFactory = new LexemeValidatorFactory(
				1000, // TODO: move to setting, at least change to some reasonable hard-coded value
				$wikibaseRepo->getTermValidatorFactory(),
				// FIXME: What does belong here?
				[]
			);
			return new LexemeChangeOpDeserializer(
				new LemmaChangeOpDeserializer(
					// TODO: WikibaseRepo should probably provide this validator?
					// TODO: WikibaseRepo::getTermsLanguage is not necessarily the list of language codes
					// that should be allowed as "languages" of lemma terms
					new TermChangeOpSerializationValidator( $wikibaseRepo->getTermsLanguages() ),
					$lexemeValidatorFactory,
					$wikibaseRepo->getStringNormalizer()
				),
				new LexicalCategoryChangeOpDeserializer(
					$lexemeValidatorFactory,
					$wikibaseRepo->getStringNormalizer()
				),
				new LanguageChangeOpDeserializer(
					$lexemeValidatorFactory,
					$wikibaseRepo->getStringNormalizer()
				),
				new ClaimsChangeOpDeserializer(
					$wikibaseRepo->getExternalFormatStatementDeserializer(),
					$wikibaseRepo->getChangeOpFactoryProvider()->getStatementChangeOpFactory()
				)
			);
		},
		'rdf-builder-factory-callback' => function (
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			$mentionedEntityTracker,
			$dedupe
		) {
			return new LexemeRdfBuilder(
				$vocabulary,
				$writer
			);
		},
		'entity-diff-visualizer-callback' => function (
			MessageLocalizer $messageLocalizer,
			ClaimDiffer $claimDiffer,
			ClaimDifferenceVisualizer $claimDiffView,
			SiteLookup $siteLookup,
			EntityIdFormatter $entityIdFormatter
		) {
			$basicEntityDiffVisualizer = new BasicEntityDiffVisualizer(
				$messageLocalizer,
				$claimDiffer,
				$claimDiffView,
				$siteLookup,
				$entityIdFormatter
			);

			return new LexemeDiffVisualizer(
				$messageLocalizer,
				$basicEntityDiffVisualizer
			);
		}
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
		'content-handler-factory-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			return new LexemeHandler(
				$wikibaseRepo->getStore()->getTermIndex(),
				$wikibaseRepo->getEntityContentDataCodec(),
				$wikibaseRepo->getEntityConstraintProvider(),
				$wikibaseRepo->getValidatorErrorLocalizer(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getEntityIdLookup(),
				$wikibaseRepo->getEntityLookup(),
				$wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory(),
				new LexemeFieldDefinitions()
			);
		},
	],
];
