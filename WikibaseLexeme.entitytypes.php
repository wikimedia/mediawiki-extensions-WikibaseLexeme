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
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lexeme\Content\LexemeContent;
use Wikibase\Lexeme\Content\LexemeHandler;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\Serialization\LexemeDeserializer;
use Wikibase\Lexeme\DataModel\Serialization\LexemeSerializer;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiffer;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemePatcher;
use Wikibase\Lexeme\View\LexemeView;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityTermsView;
use Wikibase\View\Template\TemplateFactory;

return [
	'lexeme' => [
		'serializer-factory-callback' => function( SerializerFactory $serializerFactory ) {
			return new LexemeSerializer(
				$serializerFactory->newTermSerializer(),
				$serializerFactory->newStatementListSerializer()
			);
		},
		'deserializer-factory-callback' => function( DeserializerFactory $deserializerFactory ) {
			return new LexemeDeserializer(
				$deserializerFactory->newTermDeserializer(),
				$deserializerFactory->newStatementListDeserializer()
			);
		},
		'view-factory-callback' => function(
			$languageCode,
			LabelDescriptionLookup $labelDescriptionLookup,
			LanguageFallbackChain $fallbackChain,
			EditSectionGenerator $editSectionGenerator,
			EntityTermsView $entityTermsView
		) {
			$viewFactory = WikibaseRepo::getDefaultInstance()->getViewFactory();

			return new LexemeView(
				TemplateFactory::getDefaultInstance(),
				$entityTermsView,
				$viewFactory->newStatementSectionsView(
					$languageCode,
					$labelDescriptionLookup,
					$fallbackChain,
					$editSectionGenerator
				),
				new MediaWikiLanguageDirectionalityLookup(),
				$languageCode
			);
		},
		'content-model-id' => LexemeContent::CONTENT_MODEL_ID,
		'content-handler-factory-callback' => function() {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			return new LexemeHandler(
				$wikibaseRepo->getStore()->newEntityPerPage(),
				$wikibaseRepo->getStore()->getTermIndex(),
				$wikibaseRepo->getEntityContentDataCodec(),
				$wikibaseRepo->getEntityConstraintProvider(),
				$wikibaseRepo->getValidatorErrorLocalizer(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getEntityIdLookup(),
				$wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory()
			);
		},
		'entity-id-pattern' => LexemeId::PATTERN,
		'entity-id-builder' => function( $serialization ) {
			return new LexemeId( $serialization );
		},
		'entity-id-composer-callback' => function( $repositoryName, $uniquePart ) {
			return new LexemeId( EntityId::joinSerialization( [
				$repositoryName,
				'',
				'L' . $uniquePart
			] ) );
		},
		'entity-differ-strategy-builder' => function() {
			return new LexemeDiffer();
		},
		'entity-patcher-strategy-builder' => function() {
			return new LexemePatcher();
		},
		'entity-factory-callback' => function() {
			return new Lexeme();
		},
		// Identifier of a resource loader module that, when `require`d, returns a function
		// returning a deserializer
		'js-deserializer-factory-function' => 'wikibase.lexeme.getDeserializer',
	]
];
