<?php

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
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
use Wikibase\Lexeme\Presentation\Rdf\LexemeSpecificComponentsRdfBuilder;
use Wikibase\Lexeme\Presentation\Rdf\LexemeStubRdfBuilder;
use Wikibase\Lexeme\Presentation\View\LexemeMetaTagsCreator;
use Wikibase\Lexeme\Presentation\View\LexemeViewFactory;
use Wikibase\Lexeme\Serialization\StorageLexemeSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions as Def;
use Wikibase\Lib\Formatters\NonExistingEntityIdHtmlFormatter;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\TitleLookupBasedEntityExistenceChecker;
use Wikibase\Lib\Store\TitleLookupBasedEntityRedirectChecker;
use Wikibase\Lib\Store\TitleLookupBasedEntityTitleTextLookup;
use Wikibase\Lib\Store\TitleLookupBasedEntityUrlLookup;
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
use Wikibase\Repo\Rdf\FullStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\TruthyStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Validators\EntityExistsValidator;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;

return [
	'lexeme' => [
		Def::STORAGE_SERIALIZER_FACTORY_CALLBACK => static function ( SerializerFactory $serializerFactory ) {
			return new StorageLexemeSerializer(
				$serializerFactory->newTermListSerializer(),
				$serializerFactory->newStatementListSerializer()
			);
		},
		Def::VIEW_FACTORY_CALLBACK => static function (
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
		Def::META_TAGS_CREATOR_CALLBACK => static function () {
			$services = MediaWikiServices::getInstance();
			return new LexemeMetaTagsCreator(
				RequestContext::getMain()
					->msg( 'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma' )
					->escaped(),
				WikibaseRepo::getFallbackLabelDescriptionLookupFactory()
					->newLabelDescriptionLookup( $services->getLanguageFactory()->getLanguage( 'en' ) )
			);
		},
		Def::CONTENT_MODEL_ID => LexemeContent::CONTENT_MODEL_ID,
		Def::CONTENT_HANDLER_FACTORY_CALLBACK => static function () {
			$services = MediaWikiServices::getInstance();
			$requestContext = RequestContext::getMain();
			return LexemeHandler::factory( $services, $requestContext );
		},
		Def::ENTITY_FACTORY_CALLBACK => static function () {
			return new Lexeme();
		},
		Def::CHANGEOP_DESERIALIZER_CALLBACK => static function () {
			$services = MediaWikiServices::getInstance();
			$changeOpFactoryProvider = WikibaseRepo::getChangeOpFactoryProvider( $services );
			$statementChangeOpDeserializer = new ClaimsChangeOpDeserializer(
				WikibaseRepo::getExternalFormatStatementDeserializer( $services ),
				$changeOpFactoryProvider->getStatementChangeOpFactory()
			);
			$entityLookup = WikibaseRepo::getStore( $services )->getEntityLookup(
				Store::LOOKUP_CACHING_DISABLED,
				LookupConstants::LATEST_FROM_MASTER
			);
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
					WikibaseLexemeServices::getLemmaTermValidator( $services ),
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
								WikibaseRepo::getExternalFormatStatementDeserializer(),
								$changeOpFactoryProvider->getStatementChangeOpFactory()
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
		Def::RDF_BUILDER_FACTORY_CALLBACK => static function (
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			EntityMentionListener $tracker,
			DedupeBag $dedupe
		) {
			$services = MediaWikiServices::getInstance();
			$propertyDataLookup = WikibaseRepo::getPropertyDataTypeLookup();
			$valueSnakRdfBuilderFactory = new ValueSnakRdfBuilderFactory(
				WikibaseRepo::getDataTypeDefinitions( $services )
					->getRdfBuilderFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ),
				WikibaseRepo::getLogger( $services )
			);

			$lexemeSpecificComponentsRdfBuilder = new LexemeSpecificComponentsRdfBuilder(
				$vocabulary,
				$writer,
				$tracker
			);
			$lexemeSpecificComponentsRdfBuilder->addPrefixes();

			$truthyStatementRdfBuilderFactory = new TruthyStatementRdfBuilderFactory(
				$dedupe,
				$vocabulary,
				$writer,
				$valueSnakRdfBuilderFactory,
				$tracker,
				$propertyDataLookup
			);
			$fullStatementRdfBuilderFactory = new FullStatementRdfBuilderFactory(
				$vocabulary,
				$writer,
				$valueSnakRdfBuilderFactory,
				$tracker,
				$dedupe,
				$propertyDataLookup
			);

			$rdfBuilder = new LexemeRdfBuilder(
				$flavorFlags,
				$truthyStatementRdfBuilderFactory,
				$fullStatementRdfBuilderFactory,
				$lexemeSpecificComponentsRdfBuilder
			);
			return $rdfBuilder;
		},
		Def::RDF_BUILDER_STUB_FACTORY_CALLBACK => static function (
			RdfVocabulary $vocabulary,
			RdfWriter $writer
		) {
			$entityLookup = WikibaseRepo::getEntityLookup();

			return new LexemeStubRdfBuilder(
				$vocabulary,
				$writer,
				$entityLookup
			);
		},
		Def::ENTITY_DIFF_VISUALIZER_CALLBACK => static function (
			MessageLocalizer $messageLocalizer,
			ClaimDiffer $claimDiffer,
			ClaimDifferenceVisualizer $claimDiffView,
			SiteLookup $siteLookup,
			EntityIdFormatter $entityIdFormatter
		) {
			$basicEntityDiffVisualizer = new BasicEntityDiffVisualizer(
				$messageLocalizer,
				$claimDiffer,
				$claimDiffView
			);

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
		Def::ENTITY_SEARCH_CALLBACK => static function ( WebRequest $request ) {
			return new EntityIdSearchHelper(
				WikibaseRepo::getEntityLookup(),
				WikibaseRepo::getEntityIdParser(),
				new NullLabelDescriptionLookup(),
				WikibaseRepo::getEntityTypeToRepositoryMapping()
			);
		},
		Def::LINK_FORMATTER_CALLBACK => static function ( Language $language ) {
			$requestContext = RequestContext::getMain();
			$linkFormatter = WikibaseRepo::getEntityLinkFormatterFactory()->getDefaultLinkFormatter( $language );
			/** @var $linkFormatter DefaultEntityLinkFormatter */
			'@phan-var DefaultEntityLinkFormatter $linkFormatter';

			return new LexemeLinkFormatter(
				WikibaseRepo::getEntityTitleTextLookup(),
				WikibaseLexemeServices::getLemmaLookup(),
				$linkFormatter,
				new LexemeTermFormatter(
					$requestContext
						->msg( 'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma' )
						->escaped()
				),
				$language
			);
		},
		Def::ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK => static function ( Language $language ) {
			$languageLabelLookup = WikibaseRepo::getFallbackLabelDescriptionLookupFactory()
				->newLabelDescriptionLookup( $language );
			return new LexemeIdHtmlFormatter(
				WikibaseRepo::getEntityLookup(),
				$languageLabelLookup,
				WikibaseRepo::getEntityTitleLookup(),
				new MediaWikiLocalizedTextProvider( $language ),
				new NonExistingEntityIdHtmlFormatter(
					'wikibaselexeme-deletedentity-'
				)
			);
		},
		Def::ENTITY_REFERENCE_EXTRACTOR_CALLBACK => static function () {
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
		Def::URL_LOOKUP_CALLBACK => static function () {
			return new TitleLookupBasedEntityUrlLookup( WikibaseRepo::getEntityTitleLookup() );
		},
		Def::EXISTENCE_CHECKER_CALLBACK => static function () {
			$services = MediaWikiServices::getInstance();
			return new TitleLookupBasedEntityExistenceChecker(
				WikibaseRepo::getEntityTitleLookup( $services ),
				$services->getLinkBatchFactory()
			);
		},
		Def::TITLE_TEXT_LOOKUP_CALLBACK => static function () {
			return new TitleLookupBasedEntityTitleTextLookup(
				WikibaseRepo::getEntityTitleLookup()
			);
		},
		Def::REDIRECT_CHECKER_CALLBACK => static function () {
			return new TitleLookupBasedEntityRedirectChecker( WikibaseRepo::getEntityTitleLookup() );
		},
	],
	'form' => [
		Def::CONTENT_HANDLER_FACTORY_CALLBACK => static function () {
			$services = MediaWikiServices::getInstance();
			$requestContext = RequestContext::getMain();
			return LexemeHandler::factory( $services, $requestContext );
		},
		Def::ENTITY_SEARCH_CALLBACK => static function ( WebRequest $request ) {
			return new EntityIdSearchHelper(
				WikibaseRepo::getEntityLookup(),
				WikibaseRepo::getEntityIdParser(),
				new NullLabelDescriptionLookup(),
				WikibaseRepo::getEntityTypeToRepositoryMapping()
			);
		},
		DEF::CHANGEOP_DESERIALIZER_CALLBACK => static function () {
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
		Def::ENTITY_FACTORY_CALLBACK => static function () {
			return new BlankForm();
		},
		Def::RDF_BUILDER_FACTORY_CALLBACK => static function (
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			EntityMentionListener $tracker,
			DedupeBag $dedupe
		) {
			$services = MediaWikiServices::getInstance();
			$propertyDataLookup = WikibaseRepo::getPropertyDataTypeLookup();
			$valueSnakRdfBuilderFactory = new ValueSnakRdfBuilderFactory(
				WikibaseRepo::getDataTypeDefinitions( $services )
					->getRdfBuilderFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ),
				WikibaseRepo::getLogger( $services )
			);

			$lexemeSpecificComponentsRdfBuilder = new LexemeSpecificComponentsRdfBuilder(
				$vocabulary,
				$writer,
				$tracker
			);
			$lexemeSpecificComponentsRdfBuilder->addPrefixes();

			$truthyStatementRdfBuilderFactory = new TruthyStatementRdfBuilderFactory(
				$dedupe,
				$vocabulary,
				$writer,
				$valueSnakRdfBuilderFactory,
				$tracker,
				$propertyDataLookup
			);
			$fullStatementRdfBuilderFactory = new FullStatementRdfBuilderFactory(
				$vocabulary,
				$writer,
				$valueSnakRdfBuilderFactory,
				$tracker,
				$dedupe,
				$propertyDataLookup
			);

			$rdfBuilder = new LexemeRdfBuilder(
				$flavorFlags,
				$truthyStatementRdfBuilderFactory,
				$fullStatementRdfBuilderFactory,
				$lexemeSpecificComponentsRdfBuilder
			);
			return $rdfBuilder;
		},
		Def::RDF_BUILDER_STUB_FACTORY_CALLBACK => static function (
			RdfVocabulary $vocabulary,
			RdfWriter $writer
		) {
			$entityLookup = WikibaseRepo::getEntityLookup();

			return new LexemeStubRdfBuilder(
				$vocabulary,
				$writer,
				$entityLookup
			);
		},
		Def::LINK_FORMATTER_CALLBACK => static function ( Language $language ) {
			$requestContext = RequestContext::getMain();
			$linkFormatter = WikibaseRepo::getEntityLinkFormatterFactory()->getDefaultLinkFormatter( $language );
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
		Def::ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK => static function ( Language $language ) {
			$titleLookup = WikibaseRepo::getEntityTitleLookup();
			return new FormIdHtmlFormatter(
				WikibaseRepo::getEntityRevisionLookup(),
				WikibaseRepo::getFallbackLabelDescriptionLookupFactory()
					->newLabelDescriptionLookup( $language ),
				$titleLookup,
				new MediaWikiLocalizedTextProvider( $language ),
				new RedirectedLexemeSubEntityIdHtmlFormatter( $titleLookup ),
				MediaWikiServices::getInstance()->getLanguageFactory()
			);
		},
		Def::ENTITY_METADATA_ACCESSOR_CALLBACK => static function ( $dbName, $repoName ) {
			return new MediaWikiPageSubEntityMetaDataAccessor(
				WikibaseRepo::getLocalRepoWikiPageMetaDataAccessor()
			);
		},
		Def::URL_LOOKUP_CALLBACK => static function () {
			return new TitleLookupBasedEntityUrlLookup( WikibaseRepo::getEntityTitleLookup() );
		},
		Def::EXISTENCE_CHECKER_CALLBACK => static function () {
			$services = MediaWikiServices::getInstance();
			return new TitleLookupBasedEntityExistenceChecker(
				WikibaseRepo::getEntityTitleLookup( $services ),
				$services->getLinkBatchFactory()
			);
		},
		Def::TITLE_TEXT_LOOKUP_CALLBACK => static function () {
			return new TitleLookupBasedEntityTitleTextLookup(
				WikibaseRepo::getEntityTitleLookup()
			);
		},
		Def::REDIRECT_CHECKER_CALLBACK => static function () {
			return new TitleLookupBasedEntityRedirectChecker( WikibaseRepo::getEntityTitleLookup() );
		},
	],
	'sense' => [
		Def::CONTENT_HANDLER_FACTORY_CALLBACK => static function () {
			$services = MediaWikiServices::getInstance();
			$requestContext = RequestContext::getMain();
			return LexemeHandler::factory( $services, $requestContext );
		},
		Def::ENTITY_SEARCH_CALLBACK => static function ( WebRequest $request ) {
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
				WikibaseRepo::getEntityTypeToRepositoryMapping()
			);
		},
		Def::CHANGEOP_DESERIALIZER_CALLBACK => static function () {
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
						WikibaseRepo::getExternalFormatStatementDeserializer(),
						WikibaseRepo::getChangeOpFactoryProvider()->getStatementChangeOpFactory()
					)
				)
			);
			$senseChangeOpDeserializer->setContext(
				ValidationContext::create( EditEntity::PARAM_DATA )
			);
			return $senseChangeOpDeserializer;
		},
		Def::ENTITY_FACTORY_CALLBACK => static function () {
			return new BlankSense();
		},
		Def::RDF_BUILDER_FACTORY_CALLBACK => static function (
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			EntityMentionListener $tracker,
			DedupeBag $dedupe
		) {
			$services = MediaWikiServices::getInstance();
			$propertyDataLookup = WikibaseRepo::getPropertyDataTypeLookup();
			$valueSnakRdfBuilderFactory = new ValueSnakRdfBuilderFactory(
				WikibaseRepo::getDataTypeDefinitions( $services )
					->getRdfBuilderFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ),
				WikibaseRepo::getLogger( $services )
			);

			$truthyStatementRdfBuilderFactory = new TruthyStatementRdfBuilderFactory(
				$dedupe,
				$vocabulary,
				$writer,
				$valueSnakRdfBuilderFactory,
				$tracker,
				$propertyDataLookup
			);
			$fullStatementRdfBuilderFactory = new FullStatementRdfBuilderFactory(
				$vocabulary,
				$writer,
				$valueSnakRdfBuilderFactory,
				$tracker,
				$dedupe,
				$propertyDataLookup
			);
			$lexemeSpecificComponentsRdfBuilder = new LexemeSpecificComponentsRdfBuilder(
				$vocabulary,
				$writer,
				$tracker
			);
			$lexemeSpecificComponentsRdfBuilder->addPrefixes();

			$rdfBuilder = new LexemeRdfBuilder(
				$flavorFlags,
				$truthyStatementRdfBuilderFactory,
				$fullStatementRdfBuilderFactory,
				$lexemeSpecificComponentsRdfBuilder
			);
			return $rdfBuilder;
		},
		Def::RDF_BUILDER_STUB_FACTORY_CALLBACK => static function (
			RdfVocabulary $vocabulary,
			RdfWriter $writer
		) {
			$entityLookup = WikibaseRepo::getEntityLookup();

			return new LexemeStubRdfBuilder(
				$vocabulary,
				$writer,
				$entityLookup
			);
		},
		Def::ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK => static function ( Language $language ) {

			return new SenseIdHtmlFormatter(
				WikibaseRepo::getEntityTitleLookup(),
				WikibaseRepo::getEntityRevisionLookup(),
				new MediaWikiLocalizedTextProvider( $language ),
				WikibaseRepo::getLanguageFallbackChainFactory()->newFromLanguage( $language ),
				new LanguageFallbackIndicator( WikibaseRepo::getLanguageNameLookup() ),
				MediaWikiServices::getInstance()->getLanguageFactory(),
				WikibaseRepo::getEntityIdLabelFormatterFactory()->getEntityIdFormatter( $language )
			);
		},
		Def::ENTITY_METADATA_ACCESSOR_CALLBACK => static function ( $dbName, $repoName ) {
			return new MediaWikiPageSubEntityMetaDataAccessor(
				WikibaseRepo::getLocalRepoWikiPageMetaDataAccessor()
			);
		},
		Def::URL_LOOKUP_CALLBACK => static function () {
			return new TitleLookupBasedEntityUrlLookup( WikibaseRepo::getEntityTitleLookup() );
		},
		Def::EXISTENCE_CHECKER_CALLBACK => static function () {
			$services = MediaWikiServices::getInstance();
			return new TitleLookupBasedEntityExistenceChecker(
				WikibaseRepo::getEntityTitleLookup( $services ),
				$services->getLinkBatchFactory()
			);
		},
		Def::TITLE_TEXT_LOOKUP_CALLBACK => static function () {
			return new TitleLookupBasedEntityTitleTextLookup(
				WikibaseRepo::getEntityTitleLookup()
			);
		},
		Def::REDIRECT_CHECKER_CALLBACK => static function () {
			return new TitleLookupBasedEntityRedirectChecker( WikibaseRepo::getEntityTitleLookup() );
		},
	],
];
