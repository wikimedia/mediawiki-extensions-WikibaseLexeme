<?php

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiPageSubEntityMetaDataAccessor;
use Wikibase\Lexeme\DataAccess\Store\NullLabelDescriptionLookup;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\FormsStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\GrammaticalFeatureItemIdsExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LanguageItemIdExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LexicalCategoryItemIdExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Storage\SenseLabelDescriptionLookup;
use Wikibase\Lexeme\MediaWiki\Content\LexemeContent;
use Wikibase\Lexeme\MediaWiki\Content\LexemeHandler;
use Wikibase\Lexeme\MediaWiki\EntityLinkFormatters\FormLinkFormatter;
use Wikibase\Lexeme\MediaWiki\EntityLinkFormatters\LexemeLinkFormatter;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormListChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LanguageChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LemmaChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LexemeChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LexicalCategoryChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseListChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\Presentation\Diff\ItemReferenceDifferenceVisualizer;
use Wikibase\Lexeme\Presentation\Diff\LexemeDiffVisualizer;
use Wikibase\Lexeme\Presentation\Formatters\FormIdHtmlFormatter;
use Wikibase\Lexeme\Presentation\Formatters\LexemeIdHtmlFormatter;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lexeme\Presentation\Formatters\RedirectedLexemeSubEntityIdHtmlFormatter;
use Wikibase\Lexeme\Presentation\Formatters\SenseIdHtmlFormatter;
use Wikibase\Lexeme\Presentation\Rdf\LexemeRdfBuilder;
use Wikibase\Lexeme\Presentation\View\LexemeMetaTagsCreator;
use Wikibase\Lexeme\Presentation\View\LexemeViewFactory;
use Wikibase\Lexeme\Serialization\StorageLexemeSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\EntityTypeDefinitions as Def;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\Api\EditEntity;
use Wikibase\Repo\Api\EntityIdSearchHelper;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorCollection;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\Rdf\DedupeBag;
use Wikibase\Repo\Rdf\EntityMentionListener;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Validators\EntityExistsValidator;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;

return [
	'lexeme' => [
		Def::STORAGE_SERIALIZER_FACTORY_CALLBACK => function ( SerializerFactory $serializerFactory ) {
			return new StorageLexemeSerializer(
				$serializerFactory->newTermListSerializer(),
				$serializerFactory->newStatementListSerializer()
			);
		},
		Def::VIEW_FACTORY_CALLBACK => function (
			Language $language,
			TermLanguageFallbackChain $termFallbackChain,
			EntityDocument $entity
		) {

			$factory = new LexemeViewFactory(
				$language,
				$termFallbackChain
			);

			return $factory->newLexemeView();
		},
		Def::META_TAGS_CREATOR_CALLBACK => function () {
			return new LexemeMetaTagsCreator(
				RequestContext::getMain()
					->msg( 'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma' )
					->escaped(),
				WikibaseRepo::getLanguageFallbackLabelDescriptionLookupFactory()
					->newLabelDescriptionLookup( \Language::factory( 'en' ) )
			);
		},
		Def::CONTENT_MODEL_ID => LexemeContent::CONTENT_MODEL_ID,
		Def::CONTENT_HANDLER_FACTORY_CALLBACK => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			return new LexemeHandler(
				$wikibaseRepo->getEntityContentDataCodec(),
				WikibaseRepo::getEntityConstraintProvider(),
				$wikibaseRepo->getValidatorErrorLocalizer(),
				WikibaseRepo::getEntityIdParser(),
				WikibaseRepo::getEntityIdLookup(),
				WikibaseRepo::getEntityLookup(),
				WikibaseRepo::getLanguageFallbackLabelDescriptionLookupFactory(),
				$wikibaseRepo->getFieldDefinitionsByType( Lexeme::ENTITY_TYPE )
			);
		},
		Def::ENTITY_FACTORY_CALLBACK => function () {
			return new Lexeme();
		},
		Def::CHANGEOP_DESERIALIZER_CALLBACK => function () {
			$services = MediaWikiServices::getInstance();
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$statementChangeOpDeserializer = new ClaimsChangeOpDeserializer(
				WikibaseRepo::getExternalFormatStatementDeserializer(),
				$wikibaseRepo->getChangeOpFactoryProvider()->getStatementChangeOpFactory()
			);
			$entityLookup = WikibaseRepo::getEntityLookup( $services );
			$itemValidator = new EntityExistsValidator( $entityLookup, 'item' );
			$entityIdParser = WikibaseRepo::getEntityIdParser( $services );
			$stringNormalizer = WikibaseRepo::getStringNormalizer( $services );
			$lexemeChangeOpDeserializer = new LexemeChangeOpDeserializer(
				new LemmaChangeOpDeserializer(
				// TODO: WikibaseRepo should probably provide this validator?
				// TODO: WikibaseRepo::getTermsLanguage is not necessarily the list of language codes
				// that should be allowed as "languages" of lemma terms
					new LexemeTermSerializationValidator(
						new LexemeTermLanguageValidator( WikibaseLexemeServices::getTermLanguages() )
					),
					// TODO: move to setting, at least change to some reasonable hard-coded value
					new LemmaTermValidator( 1000 ),
					$stringNormalizer
				),
				new LexicalCategoryChangeOpDeserializer(
					$itemValidator,
					$stringNormalizer
				),
				new LanguageChangeOpDeserializer(
					$itemValidator,
					$stringNormalizer
				),
				$statementChangeOpDeserializer,
				new FormListChangeOpDeserializer(
					new FormIdDeserializer( $entityIdParser ),
					new FormChangeOpDeserializer(
						$entityLookup,
						$entityIdParser,
						WikibaseLexemeServices::getEditFormChangeOpDeserializer()
					)
				),
				new SenseListChangeOpDeserializer(
					new SenseIdDeserializer( $entityIdParser ),
					new SenseChangeOpDeserializer(
						$entityLookup,
						$entityIdParser,
						new EditSenseChangeOpDeserializer(
							new GlossesChangeOpDeserializer(
								new TermDeserializer(),
								$stringNormalizer,
								new LexemeTermSerializationValidator(
									new LexemeTermLanguageValidator( WikibaseLexemeServices::getTermLanguages() )
								)
							),
							new ClaimsChangeOpDeserializer(
								$wikibaseRepo->getExternalFormatStatementDeserializer(),
								$wikibaseRepo->getChangeOpFactoryProvider()->getStatementChangeOpFactory()
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
		Def::RDF_BUILDER_FACTORY_CALLBACK => function (
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
		Def::ENTITY_DIFF_VISUALIZER_CALLBACK => function (
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
				->getEntityIdFormatter( WikibaseRepo::getUserLanguage() );

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
		Def::ENTITY_SEARCH_CALLBACK => function ( WebRequest $request ) {
			$repo = WikibaseRepo::getDefaultInstance();

			return new EntityIdSearchHelper(
				WikibaseRepo::getEntityLookup(),
				WikibaseRepo::getEntityIdParser(),
				new LanguageFallbackLabelDescriptionLookup(
					WikibaseRepo::getTermLookup(),
					WikibaseRepo::getLanguageFallbackChainFactory()
						->newFromLanguage( WikibaseRepo::getUserLanguage() )
				),
				$repo->getEntityTypeToRepositoryMapping()
			);
		},
		Def::LINK_FORMATTER_CALLBACK => function ( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$requestContext = RequestContext::getMain();
			$linkFormatter = $repo->getEntityLinkFormatterFactory( $language )->getDefaultLinkFormatter();
			/** @var $linkFormatter DefaultEntityLinkFormatter */
			'@phan-var DefaultEntityLinkFormatter $linkFormatter';

			return new LexemeLinkFormatter(
				WikibaseRepo::getEntityTitleTextLookup(),
				WikibaseRepo::getEntityLookup(),
				$linkFormatter,
				new LexemeTermFormatter(
					$requestContext
						->msg( 'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma' )
						->escaped()
				),
				$language
			);
		},
		Def::ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK => function ( Language $language ) {
			$languageLabelLookupFactory = WikibaseRepo::getLanguageFallbackLabelDescriptionLookupFactory();
			$languageLabelLookup = $languageLabelLookupFactory->newLabelDescriptionLookup( $language );
			return new LexemeIdHtmlFormatter(
				WikibaseRepo::getEntityLookup(),
				$languageLabelLookup,
				WikibaseRepo::getEntityTitleLookup(),
				new MediaWikiLocalizedTextProvider( $language )
			);
		},
		Def::ENTITY_REFERENCE_EXTRACTOR_CALLBACK => function () {
			$statementEntityReferenceExtractor = new StatementEntityReferenceExtractor(
				WikibaseRepo::getItemUrlParser()
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
	],
	'form' => [
		Def::CONTENT_HANDLER_FACTORY_CALLBACK => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$config = MediaWikiServices::getInstance()->getMainConfig();
			if ( $config->has( 'LexemeLanguageCodePropertyId' ) ) {
				$lcID = $config->get( 'LexemeLanguageCodePropertyId' );
			} else {
				$lcID = null;
			}

			return new LexemeHandler(
				$wikibaseRepo->getEntityContentDataCodec(),
				WikibaseRepo::getEntityConstraintProvider(),
				$wikibaseRepo->getValidatorErrorLocalizer(),
				WikibaseRepo::getEntityIdParser(),
				WikibaseRepo::getEntityIdLookup(),
				WikibaseRepo::getEntityLookup(),
				WikibaseRepo::getLanguageFallbackLabelDescriptionLookupFactory(),
				$wikibaseRepo->getFieldDefinitionsByType( Lexeme::ENTITY_TYPE )
			);
		},
		Def::ENTITY_SEARCH_CALLBACK => function ( WebRequest $request ) {
			// FIXME: this code should be split into extension for T190022
			$repo = WikibaseRepo::getDefaultInstance();

			return new EntityIdSearchHelper(
				WikibaseRepo::getEntityLookup(),
				WikibaseRepo::getEntityIdParser(),
				new NullLabelDescriptionLookup(),
				$repo->getEntityTypeToRepositoryMapping()
			);
		},
		DEF::CHANGEOP_DESERIALIZER_CALLBACK => function () {
			$formChangeOpDeserializer = new FormChangeOpDeserializer(
				WikibaseRepo::getEntityLookup(),
				WikibaseRepo::getEntityIdParser(),
				WikibaseLexemeServices::getEditFormChangeOpDeserializer()
			);
			$formChangeOpDeserializer->setContext(
				ValidationContext::create( EditEntity::PARAM_DATA )
			);
			return $formChangeOpDeserializer;
		},
		Def::ENTITY_FACTORY_CALLBACK => function () {
			return new BlankForm();
		},
		Def::RDF_BUILDER_FACTORY_CALLBACK => function (
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
		Def::LINK_FORMATTER_CALLBACK => function ( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$requestContext = RequestContext::getMain();
			$linkFormatter = $repo->getEntityLinkFormatterFactory( $language )->getDefaultLinkFormatter();
			/** @var $linkFormatter DefaultEntityLinkFormatter */
			'@phan-var DefaultEntityLinkFormatter $linkFormatter';

			return new FormLinkFormatter(
				WikibaseRepo::getEntityLookup(),
				$linkFormatter,
				new LexemeTermFormatter(
					$requestContext
						->msg( 'wikibaselexeme-formidformatter-separator-multiple-representation' )
						->escaped()
				),
				$language
			);
		},
		Def::ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK => function ( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$titleLookup = WikibaseRepo::getEntityTitleLookup();
			$languageLabelLookupFactory = WikibaseRepo::getLanguageFallbackLabelDescriptionLookupFactory();
			$languageLabelLookup = $languageLabelLookupFactory->newLabelDescriptionLookup( $language );
			return new FormIdHtmlFormatter(
				$repo->getEntityRevisionLookup(),
				$languageLabelLookup,
				$titleLookup,
				new MediaWikiLocalizedTextProvider( $language ),
				new RedirectedLexemeSubEntityIdHtmlFormatter( $titleLookup ),
				MediaWikiServices::getInstance()->getLanguageFactory()
			);
		},
		Def::ENTITY_METADATA_ACCESSOR_CALLBACK => function ( $dbName, $repoName ) {
			return new MediaWikiPageSubEntityMetaDataAccessor(
				WikibaseRepo::getLocalRepoWikiPageMetaDataAccessor()
			);
		},
	],
	'sense' => [
		// TODO lexemes and forms have identical content-handler-factory-callback, extract
		Def::CONTENT_HANDLER_FACTORY_CALLBACK => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$config = MediaWikiServices::getInstance()->getMainConfig();
			if ( $config->has( 'LexemeLanguageCodePropertyId' ) ) {
				$lcID = $config->get( 'LexemeLanguageCodePropertyId' );
			} else {
				$lcID = null;
			}

			return new LexemeHandler(
				$wikibaseRepo->getEntityContentDataCodec(),
				WikibaseRepo::getEntityConstraintProvider(),
				$wikibaseRepo->getValidatorErrorLocalizer(),
				WikibaseRepo::getEntityIdParser(),
				WikibaseRepo::getEntityIdLookup(),
				WikibaseRepo::getEntityLookup(),
				WikibaseRepo::getLanguageFallbackLabelDescriptionLookupFactory(),
				$wikibaseRepo->getFieldDefinitionsByType( Lexeme::ENTITY_TYPE )
			);
		},
		Def::ENTITY_SEARCH_CALLBACK => function ( WebRequest $request ) {
			// FIXME: this code should be split into extension for T190022
			$repo = WikibaseRepo::getDefaultInstance();
			$entityLookup = WikibaseRepo::getEntityLookup();
			$userLanguage = WikibaseRepo::getUserLanguage();
			$senseLabelDescriptionLookup = new SenseLabelDescriptionLookup(
				$entityLookup,
				WikibaseRepo::getLanguageFallbackChainFactory()->newFromLanguage( $userLanguage ),
				new MediaWikiLocalizedTextProvider( $userLanguage )
			);

			return new EntityIdSearchHelper(
				$entityLookup,
				WikibaseRepo::getEntityIdParser(),
				$senseLabelDescriptionLookup,
				$repo->getEntityTypeToRepositoryMapping()
			);
		},
		Def::CHANGEOP_DESERIALIZER_CALLBACK => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$senseChangeOpDeserializer = new SenseChangeOpDeserializer(
				WikibaseRepo::getEntityLookup(),
				WikibaseRepo::getEntityIdParser(),
				new EditSenseChangeOpDeserializer(
					new GlossesChangeOpDeserializer(
						new TermDeserializer(),
						WikibaseRepo::getStringNormalizer(),
						new LexemeTermSerializationValidator(
							new LexemeTermLanguageValidator( WikibaseLexemeServices::getTermLanguages() )
						)
					),
					new ClaimsChangeOpDeserializer(
						$wikibaseRepo->getExternalFormatStatementDeserializer(),
						$wikibaseRepo->getChangeOpFactoryProvider()->getStatementChangeOpFactory()
					)
				)
			);
			$senseChangeOpDeserializer->setContext(
				ValidationContext::create( EditEntity::PARAM_DATA )
			);
			return $senseChangeOpDeserializer;
		},
		Def::ENTITY_FACTORY_CALLBACK => function () {
			return new BlankSense();
		},
		Def::RDF_BUILDER_FACTORY_CALLBACK => function (
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
		Def::ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK => function ( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();

			return new SenseIdHtmlFormatter(
				WikibaseRepo::getEntityTitleLookup(),
				$repo->getEntityRevisionLookup(),
				new MediaWikiLocalizedTextProvider( $language ),
				WikibaseRepo::getLanguageFallbackChainFactory()->newFromLanguage( $language ),
				new LanguageFallbackIndicator( WikibaseRepo::getLanguageNameLookup() ),
				MediaWikiServices::getInstance()->getLanguageFactory()
			);
		},
		Def::ENTITY_METADATA_ACCESSOR_CALLBACK => function ( $dbName, $repoName ) {
			return new MediaWikiPageSubEntityMetaDataAccessor(
				WikibaseRepo::getLocalRepoWikiPageMetaDataAccessor()
			);
		},
	],
];
