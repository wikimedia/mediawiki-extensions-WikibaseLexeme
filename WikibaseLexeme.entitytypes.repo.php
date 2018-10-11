<?php

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\InProcessCachingDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lexeme\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\FormChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\FormListChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LanguageChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LemmaChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LexemeChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LexicalCategoryChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\SenseChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\SenseListChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\Diff\ItemReferenceDifferenceVisualizer;
use Wikibase\Lexeme\Diff\LexemeDiffVisualizer;
use Wikibase\Lexeme\Domain\Lookups\SenseLabelDescriptionLookup;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Serialization\StorageLexemeSerializer;
use Wikibase\Lexeme\DummyObjects\BlankForm;
use Wikibase\Lexeme\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\FormsStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\GrammaticalFeatureItemIdsExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LanguageItemIdExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LexicalCategoryItemIdExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Formatters\FormIdHtmlFormatter;
use Wikibase\Lexeme\Formatters\LexemeIdHtmlFormatter;
use Wikibase\Lexeme\Formatters\LexemeTermFormatter;
use Wikibase\Lexeme\Formatters\RedirectedLexemeSubEntityIdHtmlFormatter;
use Wikibase\Lexeme\Formatters\SenseIdHtmlFormatter;
use Wikibase\Lexeme\MediaWiki\Content\LexemeContent;
use Wikibase\Lexeme\MediaWiki\Content\LexemeHandler;
use Wikibase\Lexeme\MediaWiki\EntityLinkFormatters\FormLinkFormatter;
use Wikibase\Lexeme\MediaWiki\EntityLinkFormatters\LexemeLinkFormatter;
use Wikibase\Lexeme\Rdf\LexemeRdfBuilder;
use Wikibase\Lexeme\Search\LexemeFieldDefinitions;
use Wikibase\Lexeme\Store\NullLabelDescriptionLookup;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Lexeme\View\LexemeMetaTagsCreator;
use Wikibase\Lexeme\View\LexemeViewFactory;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Rdf\DedupeBag;
use Wikibase\Rdf\EntityMentionListener;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Repo\Api\EditEntity;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorCollection;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\Search\Elastic\Fields\StatementProviderFieldDefinitions;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityTermsView;
use Wikimedia\Purtle\RdfWriter;

return [
	'lexeme' => [
		'storage-serializer-factory-callback' => function ( SerializerFactory $serializerFactory ) {
			return new StorageLexemeSerializer(
				$serializerFactory->newTermListSerializer(),
				$serializerFactory->newStatementListSerializer()
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
		'meta-tags-creator-callback' => function () {
			return new LexemeMetaTagsCreator(
				RequestContext::getMain()
					->msg( 'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma' )
					->escaped()
			);
		},
		'content-model-id' => LexemeContent::CONTENT_MODEL_ID,
		'search-field-definitions' => function ( array $languageCodes, SettingsArray $searchSettings ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$config = MediaWikiServices::getInstance()->getMainConfig();
			if ( $config->has( 'LexemeLanguageCodePropertyId' ) ) {
				$lcID = $config->get( 'LexemeLanguageCodePropertyId' );
			} else {
				$lcID = null;
			}
			return new LexemeFieldDefinitions(
				StatementProviderFieldDefinitions::newFromSettings(
					new InProcessCachingDataTypeLookup( $repo->getPropertyDataTypeLookup() ),
					$repo->getDataTypeDefinitions()->getSearchIndexDataFormatterCallbacks(),
					$searchSettings
				),
				$repo->getEntityLookup(),
				$lcID ? $repo->getEntityIdParser()->parse( $lcID ) : null
			);
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
				$wikibaseRepo->getFieldDefinitionsByType( Lexeme::ENTITY_TYPE )
			);
		},
		'entity-factory-callback' => function () {
			return new Lexeme();
		},
		'changeop-deserializer-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$lexemeValidatorFactory = new LexemeValidatorFactory(
				1000, // TODO: move to setting, at least change to some reasonable hard-coded value
				$wikibaseRepo->getTermValidatorFactory(),
				// FIXME: What does belong here?
				[]
			);
			$statementChangeOpDeserializer = new ClaimsChangeOpDeserializer(
				$wikibaseRepo->getExternalFormatStatementDeserializer(),
				$wikibaseRepo->getChangeOpFactoryProvider()->getStatementChangeOpFactory()
			);
			$lexemeChangeOpDeserializer = new LexemeChangeOpDeserializer(
				new LemmaChangeOpDeserializer(
				// TODO: WikibaseRepo should probably provide this validator?
				// TODO: WikibaseRepo::getTermsLanguage is not necessarily the list of language codes
				// that should be allowed as "languages" of lemma terms
					new LexemeTermSerializationValidator(
						new LexemeTermLanguageValidator( WikibaseLexemeServices::getTermLanguages() )
					),
					$lexemeValidatorFactory->getLemmaTermValidator(),
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
				$statementChangeOpDeserializer,
				new FormListChangeOpDeserializer(
					new FormIdDeserializer( $wikibaseRepo->getEntityIdParser() ),
					new FormChangeOpDeserializer(
						$wikibaseRepo->getEntityLookup(),
						$wikibaseRepo->getEntityIdParser(),
						WikibaseLexemeServices::getEditFormChangeOpDeserializer()
					)
				),
				new SenseListChangeOpDeserializer(
					new SenseIdDeserializer( $wikibaseRepo->getEntityIdParser() ),
					new SenseChangeOpDeserializer(
						$wikibaseRepo->getEntityLookup(),
						$wikibaseRepo->getEntityIdParser(),
						new EditSenseChangeOpDeserializer(
							new GlossesChangeOpDeserializer(
								new TermDeserializer(),
								new LexemeTermSerializationValidator(
									new LexemeTermLanguageValidator( WikibaseLexemeServices::getTermLanguages() )
								)
							)
						)
					)
				)
			);
			$lexemeChangeOpDeserializer->setContext(
				ValidationContext::create( EditEntity::PARAM_DATA )
			);
			return $lexemeChangeOpDeserializer;
		},
		'rdf-builder-factory-callback' => function (
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			EntityMentionListener $tracker,
			DedupeBag $dedupe
		) {
			$rdfBuilder = new LexemeRdfBuilder(
				$vocabulary,
				$writer,
				$tracker
			);
			$rdfBuilder->addPrefixes();
			return $rdfBuilder;
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

			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			$entityIdFormatter = $wikibaseRepo->getEntityIdHtmlLinkFormatterFactory()
				->getEntityIdFormatter( $wikibaseRepo->getUserLanguage() );

			return new LexemeDiffVisualizer(
				$messageLocalizer,
				$basicEntityDiffVisualizer,
				$claimDiffer,
				$claimDiffView,
				new ItemReferenceDifferenceVisualizer(
					$entityIdFormatter
				)
			);
		},
		'entity-search-callback' => function ( WebRequest $request ) {
			// FIXME: this code should be split into extension for T190022
			$repo = WikibaseRepo::getDefaultInstance();

			$repoSettings = $repo->getSettings();
			$searchSettings = $repoSettings->getSetting( 'entitySearch' );
			if ( $searchSettings['useCirrus'] ) {
				return new \Wikibase\Lexeme\Search\LexemeSearchEntity(
					$repo->getEntityIdParser(),
					$request,
					$repo->getUserLanguage(),
					$repo->getLanguageFallbackChainFactory(),
					$repo->getPrefetchingTermLookup()
				);
			}

			return new Wikibase\Repo\Api\EntityIdSearchHelper(
				$repo->getEntityLookup(),
				$repo->getEntityIdParser(),
				new Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup(
					$repo->getTermLookup(),
					$repo->getLanguageFallbackChainFactory()->newFromLanguage( $repo->getUserLanguage() )
				),
				$repo->getEntityTypeToRepositoryMapping()
			);
		},
		'link-formatter-callback' => function ( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$requestContext = RequestContext::getMain();

			return new LexemeLinkFormatter(
				$repo->getEntityLookup(),
				new DefaultEntityLinkFormatter( $language ),
				new LexemeTermFormatter(
					$requestContext
						->msg( 'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma' )
						->escaped()
				),
				$language
			);
		},
		'entity-id-html-link-formatter-callback' => function( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$languageLabelLookupFactory = $repo->getLanguageFallbackLabelDescriptionLookupFactory();
			$languageLabelLookup = $languageLabelLookupFactory->newLabelDescriptionLookup( $language );
			return new LexemeIdHtmlFormatter(
				$repo->getEntityLookup(),
				$languageLabelLookup,
				$repo->getEntityTitleLookup(),
				new MediaWikiLocalizedTextProvider( $language->getCode() )
			);
		},
		'entity-reference-extractor-callback' => function () {
			$statementEntityReferenceExtractor = new StatementEntityReferenceExtractor(
				WikibaseRepo::getDefaultInstance()->getLocalItemUriParser()
			);
			return new EntityReferenceExtractorCollection( [
				new LanguageItemIdExtractor(),
				new LexicalCategoryItemIdExtractor(),
				new GrammaticalFeatureItemIdsExtractor(),
				$statementEntityReferenceExtractor,
				new FormsStatementEntityReferenceExtractor( $statementEntityReferenceExtractor ),
				new SensesStatementEntityReferenceExtractor( $statementEntityReferenceExtractor ),
			] );
		},
		'fulltext-search-context' => 'wikibase_lexeme_fulltext',
			// TODO: use LexemeFullTextQueryBuilder::CONTEXT_LEXEME_FULLTEXT
			//when possible to not crash on non-Cirrus setup
	],
	'form' => [
		'content-handler-factory-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$config = MediaWikiServices::getInstance()->getMainConfig();
			if ( $config->has( 'LexemeLanguageCodePropertyId' ) ) {
				$lcID = $config->get( 'LexemeLanguageCodePropertyId' );
			} else {
				$lcID = null;
			}

			return new LexemeHandler(
				$wikibaseRepo->getStore()->getTermIndex(),
				$wikibaseRepo->getEntityContentDataCodec(),
				$wikibaseRepo->getEntityConstraintProvider(),
				$wikibaseRepo->getValidatorErrorLocalizer(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getEntityIdLookup(),
				$wikibaseRepo->getEntityLookup(),
				$wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory(),
				new LexemeFieldDefinitions(
					$wikibaseRepo->getStatementProviderDefinitions(),
					$wikibaseRepo->getEntityLookup(),
					$lcID ? $wikibaseRepo->getEntityIdParser()->parse( $lcID ) : null
				)
			);
		},
		'entity-search-callback' => function ( WebRequest $request ) {
			// FIXME: this code should be split into extension for T190022
			$repo = WikibaseRepo::getDefaultInstance();

			$repoSettings = $repo->getSettings();
			$searchSettings = $repoSettings->getSetting( 'entitySearch' );
			if ( $searchSettings['useCirrus'] ) {
				return new \Wikibase\Lexeme\Search\FormSearchEntity(
					$repo->getEntityIdParser(),
					$request,
					$repo->getUserLanguage(),
					$repo->getLanguageFallbackChainFactory(),
					$repo->getPrefetchingTermLookup()
				);
			}

			return new Wikibase\Repo\Api\EntityIdSearchHelper(
				$repo->getEntityLookup(),
				$repo->getEntityIdParser(),
				new NullLabelDescriptionLookup(),
				$repo->getEntityTypeToRepositoryMapping()
			);
		},
		'changeop-deserializer-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$formChangeOpDeserializer = new FormChangeOpDeserializer(
				$wikibaseRepo->getEntityLookup(),
				$wikibaseRepo->getEntityIdParser(),
				WikibaseLexemeServices::getEditFormChangeOpDeserializer()
			);
			$formChangeOpDeserializer->setContext(
				ValidationContext::create( EditEntity::PARAM_DATA )
			);
			return $formChangeOpDeserializer;
		},
		'entity-factory-callback' => function () {
			return new BlankForm();
		},
		'rdf-builder-factory-callback' => function (
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			EntityMentionListener $tracker,
			DedupeBag $dedupe
		) {
			$rdfBuilder = new LexemeRdfBuilder(
				$vocabulary,
				$writer,
				$tracker
			);
			$rdfBuilder->addPrefixes();
			return $rdfBuilder;
		},
		'link-formatter-callback' => function ( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$requestContext = RequestContext::getMain();

			return new FormLinkFormatter(
				$repo->getEntityLookup(),
				new DefaultEntityLinkFormatter( $language ),
				new LexemeTermFormatter(
					$requestContext
						->msg( 'wikibaselexeme-formidformatter-separator-multiple-representation' )
						->escaped()
				),
				$language
			);
		},
		'entity-id-html-link-formatter-callback' => function( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$titleLookup = $repo->getEntityTitleLookup();
			return new FormIdHtmlFormatter(
				$repo->getEntityRevisionLookup(),
				$titleLookup,
				new MediaWikiLocalizedTextProvider( $language->getCode() ),
				new RedirectedLexemeSubEntityIdHtmlFormatter( $titleLookup )
			);
		},
	],
	'sense' => [
		// TODO lexemes and forms have identical content-handler-factory-callback, extract
		'content-handler-factory-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$config = MediaWikiServices::getInstance()->getMainConfig();
			if ( $config->has( 'LexemeLanguageCodePropertyId' ) ) {
				$lcID = $config->get( 'LexemeLanguageCodePropertyId' );
			} else {
				$lcID = null;
			}

			return new LexemeHandler(
				$wikibaseRepo->getStore()->getTermIndex(),
				$wikibaseRepo->getEntityContentDataCodec(),
				$wikibaseRepo->getEntityConstraintProvider(),
				$wikibaseRepo->getValidatorErrorLocalizer(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getEntityIdLookup(),
				$wikibaseRepo->getEntityLookup(),
				$wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory(),
				new LexemeFieldDefinitions(
					$wikibaseRepo->getStatementProviderDefinitions(),
					$wikibaseRepo->getEntityLookup(),
					$lcID ? $wikibaseRepo->getEntityIdParser()->parse( $lcID ) : null
				)
			);
		},
		'entity-search-callback' => function ( WebRequest $request ) {
			// FIXME: this code should be split into extension for T190022
			$repo = WikibaseRepo::getDefaultInstance();
			$entityLookup = $repo->getEntityLookup();
			$userLanguage = $repo->getUserLanguage();
			$senseLabelDescriptionLookup = new SenseLabelDescriptionLookup(
				$entityLookup,
				$repo->getLanguageFallbackChainFactory()->newFromLanguage( $userLanguage ),
				new MediaWikiLocalizedTextProvider( $userLanguage->getCode() )
			);

			return new Wikibase\Repo\Api\EntityIdSearchHelper(
				$entityLookup,
				$repo->getEntityIdParser(),
				$senseLabelDescriptionLookup,
				$repo->getEntityTypeToRepositoryMapping()
			);
		},
		'changeop-deserializer-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$senseChangeOpDeserializer = new SenseChangeOpDeserializer(
				$wikibaseRepo->getEntityLookup(),
				$wikibaseRepo->getEntityIdParser(),
				new EditSenseChangeOpDeserializer(
					new GlossesChangeOpDeserializer(
						new TermDeserializer(),
						new LexemeTermSerializationValidator(
							new LexemeTermLanguageValidator( WikibaseLexemeServices::getTermLanguages() )
						)
					)
				)
			);
			$senseChangeOpDeserializer->setContext(
				ValidationContext::create( EditEntity::PARAM_DATA )
			);
			return $senseChangeOpDeserializer;
		},
		'entity-factory-callback' => function () {
			return new BlankSense();
		},
		'rdf-builder-factory-callback' => function (
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			EntityMentionListener $tracker,
			DedupeBag $dedupe
		) {
			$rdfBuilder = new LexemeRdfBuilder(
				$vocabulary,
				$writer,
				$tracker
			);
			$rdfBuilder->addPrefixes();
			return $rdfBuilder;
		},
		'entity-id-html-link-formatter-callback' => function( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();

			return new SenseIdHtmlFormatter(
				$repo->getEntityTitleLookup(),
				$repo->getEntityRevisionLookup(),
				new MediaWikiLocalizedTextProvider( $language->getCode() ),
				$repo->getLanguageFallbackChainFactory()->newFromLanguage( $language ),
				new LanguageFallbackIndicator( $repo->getLanguageNameLookup() )
			);
		},
	],
];
