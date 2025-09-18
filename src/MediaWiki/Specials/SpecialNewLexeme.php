<?php
declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Specials;

use Exception;
use MediaWiki\Config\ConfigException;
use MediaWiki\Exception\UserBlockedError;
use MediaWiki\Html\Html;
use MediaWiki\Html\TemplateParser;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Language\LanguageCode;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Status\Status;
use MediaWiki\User\TempUser\TempUserConfig;
use OOUI\IconWidget;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\MediaWiki\Specials\HTMLForm\LemmaLanguageField;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Summary;
use Wikibase\Repo\AnonymousEditWarningBuilder;
use Wikibase\Repo\CopyrightMessageBuilder;
use Wikibase\Repo\EditEntity\EditEntity;
use Wikibase\Repo\EditEntity\EditEntityStatus;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\Specials\HTMLForm\HTMLItemReferenceField;
use Wikibase\Repo\Specials\HTMLForm\HTMLTrimmedTextField;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\View\EntityIdFormatterFactory;
use Wikimedia\Stats\StatsFactory;

/**
 * New page for creating new Lexeme entities.
 *
 * @license GPL-2.0-or-later
 */
class SpecialNewLexeme extends SpecialPage {

	public const FIELD_LEXEME_LANGUAGE = 'lexeme-language';
	public const FIELD_LEXICAL_CATEGORY = 'lexicalcategory';
	public const FIELD_LEMMA = 'lemma';
	public const FIELD_LEMMA_LANGUAGE = 'lemma-language';

	// used for the info panel and placeholders if the example lexeme is incomplete/missing
	private const FALLBACK_LANGUAGE_LABEL = 'English';
	private const FALLBACK_LEXICAL_CATEGORY_LABEL = 'verb';

	private array $tags;
	private LinkRenderer $linkRenderer;
	private StatsFactory $statsFactory;
	private MediaWikiEditEntityFactory $editEntityFactory;
	private EntityNamespaceLookup $entityNamespaceLookup;
	private EntityTitleStoreLookup $entityTitleLookup;
	private EntityLookup $entityLookup;
	private EntityIdParser $entityIdParser;
	private SummaryFormatter $summaryFormatter;
	private EntityIdFormatterFactory $entityIdFormatterFactory;
	private FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory;
	private ValidatorErrorLocalizer $validatorErrorLocalizer;
	private LemmaTermValidator $lemmaTermValidator;
	private SpecialPageCopyrightView $copyrightView;
	private AnonymousEditWarningBuilder $anonymousEditWarningBuilder;
	private TempUserConfig $tempUserConfig;

	public function __construct(
		array $tags,
		SpecialPageCopyrightView $copyrightView,
		LinkRenderer $linkRenderer,
		StatsFactory $statsFactory,
		MediaWikiEditEntityFactory $editEntityFactory,
		EntityNamespaceLookup $entityNamespaceLookup,
		EntityTitleStoreLookup $entityTitleLookup,
		EntityLookup $entityLookup,
		EntityIdParser $entityIdParser,
		SummaryFormatter $summaryFormatter,
		EntityIdFormatterFactory $entityIdFormatterFactory,
		FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		ValidatorErrorLocalizer $validatorErrorLocalizer,
		LemmaTermValidator $lemmaTermValidator,
		AnonymousEditWarningBuilder $anonymousEditWarningBuilder,
		TempUserConfig $tempUserConfig
	) {
		parent::__construct(
			'NewLexeme',
			'createpage'
		);

		$this->tags = $tags;
		$this->linkRenderer = $linkRenderer;
		$this->statsFactory = $statsFactory;
		$this->editEntityFactory = $editEntityFactory;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityLookup = $entityLookup;
		$this->entityIdParser = $entityIdParser;
		$this->summaryFormatter = $summaryFormatter;
		$this->entityIdFormatterFactory = $entityIdFormatterFactory;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->validatorErrorLocalizer = $validatorErrorLocalizer;
		$this->lemmaTermValidator = $lemmaTermValidator;
		$this->copyrightView = $copyrightView;
		$this->anonymousEditWarningBuilder = $anonymousEditWarningBuilder;
		$this->tempUserConfig = $tempUserConfig;
	}

	public static function factory(
		LinkRenderer $linkRenderer,
		StatsFactory $statsFactory,
		TempUserConfig $tempUserConfig,
		AnonymousEditWarningBuilder $anonymousEditWarningBuilder,
		MediaWikiEditEntityFactory $editEntityFactory,
		EntityNamespaceLookup $entityNamespaceLookup,
		EntityTitleStoreLookup $entityTitleLookup,
		EntityLookup $entityLookup,
		EntityIdParser $entityIdParser,
		SettingsArray $repoSettings,
		SummaryFormatter $summaryFormatter,
		EntityIdFormatterFactory $entityIdFormatterFactory,
		FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		ValidatorErrorLocalizer $validatorErrorLocalizer,
		LemmaTermValidator $lemmaTermValidator
	): self {
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$repoSettings->getSetting( 'dataRightsUrl' ),
			$repoSettings->getSetting( 'dataRightsText' )
		);

		return new self(
			$repoSettings->getSetting( 'specialPageTags' ),
			$copyrightView,
			$linkRenderer,
			$statsFactory,
			$editEntityFactory,
			$entityNamespaceLookup,
			$entityTitleLookup,
			$entityLookup,
			$entityIdParser,
			$summaryFormatter,
			$entityIdFormatterFactory,
			$labelDescriptionLookupFactory,
			$validatorErrorLocalizer,
			$lemmaTermValidator,
			$anonymousEditWarningBuilder,
			$tempUserConfig
		);
	}

	public function doesWrites(): bool {
		return true;
	}

	/**
	 * @param string|null $subPage
	 */
	public function execute( $subPage ): void {
		$metric = $this->statsFactory->getCounter( 'special_new_lexeme_views_total' );
		$metric->copyToStatsdAt( 'wikibase.lexeme.special.NewLexeme.views' )->increment();

		parent::execute( $subPage );

		$this->checkBlocked();
		$this->checkBlockedOnNamespace();
		$this->checkReadOnly();

		$output = $this->getOutput();
		$this->setHeaders();
		$searchUrl = SpecialPage::getTitleFor( 'Search' )
			->getFullURL( [
				'ns' . $this->getConfig()->get( 'LexemeNamespace' ) => '',
				'search' => $this->getRequest()->getText( self::FIELD_LEMMA ),
			] );
		$searchExisting = $this->msg( 'wikibaselexeme-newlexeme-search-existing' )
			->params( $searchUrl )
			->parse();
		$output->addHTML(
			'<div id="wbl-snl-intro-text-wrapper">'
			. '<p class="wbl-snl-search-existing-no-js">' . $searchExisting . '</p>'
			. '</div>'
		);
		$output->enableOOUI();
		$this->showAnonymousEditWarning();
		$output->addHTML( '<div class="wbl-snl-main-content">' );
		$output->addHTML( '<div id="special-newlexeme-root"></div>' );
		$output->addModules( [
			'wikibase.lexeme.special.NewLexeme',
			'wikibase.lexeme.special.NewLexeme.legacyBrowserFallback',
			] );
		$output->addModuleStyles( [
			'wikibase.lexeme.special.NewLexeme.styles',
			'wikibase.alltargets', // T322687
		] );

		$exampleLexemeParams = $this->createExampleParameters();
		$form = $this->createForm( $exampleLexemeParams );
		$form->setSubmitText( $this->msg( 'wikibaselexeme-newlexeme-submit' ) );

		// handle submit (submit callback may create form, see below)
		// or show form (possibly with errors); status represents submit result
		$status = $form->show();
		$output->addModuleStyles( [
			'oojs-ui.styles.icons-content', // info icon
			'oojs-ui.styles.icons-alert', // alert icon
		] );
		$output->addHTML(
			$this->processInfoPanelTemplate( $exampleLexemeParams )
		);
		$output->addHTML( '</div>' ); // .wbl-snl-main-content
		$output->addHTML(
			'<noscript>'
			. '<style type="text/css">#special-newlexeme-root {display:none;}</style>'
			. '</noscript>'
		);

		if ( $status instanceof Status && $status->isGood() ) {
			// wrap it, in case HTMLForm turned it into a generic Status
			$status = EditEntityStatus::wrap( $status );
			$this->redirectToEntityPage( $status );
			return;
		}

		$output->addJsConfigVars( 'wblSpecialNewLexemeParams',
			$this->getUrlParamsForConfig()
		);
		$output->addJsConfigVars(
			'wblSpecialNewLexemeLexicalCategorySuggestions',
			$this->getLexicalCategorySuggestions()
		);
		$output->addJsConfigVars( 'wblSpecialNewLexemeTempUserEnabled',
			$this->tempUserConfig->isEnabled()
		);
		$output->addJSConfigVars(
			'wblSpecialNewLexemeExampleData',
			[
				'languageLabel' => $exampleLexemeParams['language_item_label'],
				'lexicalCategoryLabel' => $exampleLexemeParams['lexical_category_item_label'],
				'lemma' => $exampleLexemeParams['lemma_text'],
				'spellingVariant' => $exampleLexemeParams['lemma_language'],
			]
		);
	}

	private function getUrlParamsForConfig(): array {
		$params = [];
		$lemma = $this->getRequest()->getText( self::FIELD_LEMMA );
		if ( $lemma ) {
			$params['lemma'] = $lemma;
		}

		$spellVarCode = $this->getRequest()->getText( self::FIELD_LEMMA_LANGUAGE );
		if ( $spellVarCode ) {
			$params['spellVarCode'] = $spellVarCode;
		}

		try {
			$languageId = $this->entityIdParser->parse(
				$this->getRequest()->getText( self::FIELD_LEXEME_LANGUAGE )
			);
		} catch ( EntityIdParsingException ) {
			$languageId = null;
		}
		try {
			$lexCatId = $this->entityIdParser->parse(
				$this->getRequest()->getText( self::FIELD_LEXICAL_CATEGORY )
			);
		} catch ( EntityIdParsingException ) {
			$lexCatId = null;
		}

		$idsToPrefetch = array_filter( [ $languageId, $lexCatId ] );
		if ( !$idsToPrefetch ) {
			return $params;
		}

		$labelDescriptionLookup = $this->labelDescriptionLookupFactory->newLabelDescriptionLookup(
			$this->getLanguage(),
			$idsToPrefetch,
			[ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION ]
		);

		if ( $languageId ) {
			$params['language'] = $this->getItemIdLabelDesc( $languageId, $labelDescriptionLookup );
			$params['language']['languageCode'] = $this->extractLanguageCode( $languageId );
		}

		if ( $lexCatId ) {
			$params['lexicalCategory'] = $this->getItemIdLabelDesc( $lexCatId, $labelDescriptionLookup );
		}

		return $params;
	}

	private function getItemIdLabelDesc(
		EntityId $itemId,
		FallbackLabelDescriptionLookup $labelDescriptionLookup
	): array {
		$params = [ 'display' => [] ];
		$params['id'] = $itemId->getSerialization();
		$label = $labelDescriptionLookup->getLabel( $itemId );
		if ( $label !== null ) {
			$params['display']['label'] = self::termToArrayForJs( $label );
		}
		$description = $labelDescriptionLookup->getDescription( $itemId );
		if ( $description !== null ) {
			$params['display']['description'] = self::termToArrayForJs( $description );
		}

		return $params;
	}

	/** @return mixed|null */
	private function extractLanguageCode( EntityId $languageId ) {
		$lexemeLanguageCodePropertyIdString = $this->getConfig()->get( 'LexemeLanguageCodePropertyId' );
		if ( !$lexemeLanguageCodePropertyIdString ) {
			return null;
		}
		$languageItem = $this->entityLookup->getEntity( $languageId );
		if ( !( $languageItem instanceof Item ) ) {
			return null;
		}
		$lexemeLanguageCodePropertyId = $this->entityIdParser->parse(
			$lexemeLanguageCodePropertyIdString
		);
		if ( !( $lexemeLanguageCodePropertyId instanceof PropertyId ) ) {
			throw new ConfigException(
				'LexemeLanguageCodePropertyId must be a property ID, but isn’t: ' . $lexemeLanguageCodePropertyIdString
			);
		}
		$languageCodeStatements = $languageItem->getStatements()->getByPropertyId(
			$lexemeLanguageCodePropertyId
		)->getBestStatements();
		if ( !$languageCodeStatements->isEmpty() ) {
			$firstBestSnak = $languageCodeStatements->getMainSnaks()[0];
			if ( $firstBestSnak instanceof PropertyValueSnak ) {
				return $firstBestSnak->getDataValue()->getValue();
			}
			if ( $firstBestSnak instanceof PropertySomeValueSnak ) {
				return false;
			}
		}
		return null;
	}

	private function createExampleParameters(): array {
		$exampleMessage = $this->msg( 'wikibaselexeme-newlexeme-info-panel-example-lexeme-id' );
		if ( $exampleMessage->exists() ) {
			$lexemeIdString = trim( $exampleMessage->text() );
		} else {
			$lexemeIdString = 'L1';
		}
		try {
			return $this->createTemplateParamsFromLexemeId( $lexemeIdString );
		} catch ( Exception $_ ) {
			return [
				'lexeme_id_HTML' => 'L1',
				'lemma_text' => 'speak',
				'lemma_language' => 'en',
				'language_item_id' => 'Q1',
				'language_item_label' => self::FALLBACK_LANGUAGE_LABEL,
				'language_link_HTML' => self::FALLBACK_LANGUAGE_LABEL,
				'lexical_category_item_id' => 'Q2',
				'lexical_category_item_label' => self::FALLBACK_LEXICAL_CATEGORY_LABEL,
				'lexical_category_link_HTML' => self::FALLBACK_LEXICAL_CATEGORY_LABEL,
			];
		}
	}

	private function createTemplateParamsFromLexemeId( string $lexemeIdString ): array {
		try {
			$lexemeId = $this->entityIdParser->parse( $lexemeIdString );
			$lexeme = $this->entityLookup->getEntity( $lexemeId );
		} catch ( EntityIdParsingException ) {
			$lexeme = null;
		}
		if ( !( $lexeme instanceof Lexeme ) ) {
			throw new ConfigException(
				'MediaWiki:wikibaselexeme-newlexeme-info-panel-example-lexeme-id must be ' .
				'the ID of an existing lexeme, but isn’t: ' . $lexemeIdString
			);
		}

		$lemma = $lexeme->getLemmas()->getIterator()->current();
		$lexemeIdLink = $this->linkRenderer->makeKnownLink(
			$this->entityTitleLookup->getTitleForId( $lexemeId ),
			$lexemeIdString
		);

		$labelDescriptionLookup = $this->labelDescriptionLookupFactory->newLabelDescriptionLookup(
			$this->getLanguage(),
			[ $lexeme->getLanguage(), $lexeme->getLexicalCategory() ],
			[ TermTypes::TYPE_LABEL ]
		);

		$entityIdFormatter = $this->entityIdFormatterFactory->getEntityIdFormatter( $this->getLanguage() );
		$languageLabel = $labelDescriptionLookup->getLabel( $lexeme->getLanguage() );
		$lexicalCategoryLabel = $labelDescriptionLookup->getLabel( $lexeme->getLexicalCategory() );

		return [
			'lexeme_id_HTML' => $lexemeIdLink,
			'lemma_text' => $lemma->getText(),
			'lemma_language' => $lemma->getLanguageCode(),
			'language_item_id' => $lexeme->getLanguage()->getSerialization(),
			'language_item_label' => $languageLabel ?
				$languageLabel->getText() :
				self::FALLBACK_LANGUAGE_LABEL,
			'language_link_HTML' => $entityIdFormatter->formatEntityId( $lexeme->getLanguage() ),
			'lexical_category_item_id' => $lexeme->getLexicalCategory()->getSerialization(),
			'lexical_category_item_label' => $lexicalCategoryLabel ?
				$lexicalCategoryLabel->getText() :
				self::FALLBACK_LEXICAL_CATEGORY_LABEL,
			'lexical_category_link_HTML' => $entityIdFormatter->formatEntityId( $lexeme->getLexicalCategory() ),
		];
	}

	private function processInfoPanelTemplate( array $params ): string {
		$staticTemplateParams = [
			'header' => $this->msg( 'wikibaselexeme-newlexeme-info-panel-heading' )->text(),
			'lexicographical-data_HTML' => $this->msg(
				'wikibaselexeme-newlexeme-info-panel-lexicographical-data'
			)->parse(),
			'no-general-data_HTML' => $this->msg( 'wikibaselexeme-newlexeme-info-panel-no-general-data' )->parse(),
			'info_icon_HTML' => ( new IconWidget( [ 'icon' => 'infoFilled' ] ) )->toString(),
			'language_label' => $this->msg( 'wikibaselexeme-field-language-label' )->text(),
			'lexical_category_label' => $this->msg(
				'wikibaselexeme-field-lexical-category-label'
			)->text(),
			'colon_separator' => $this->msg( 'colon-separator' )->text(),
		];
		$params['lemma_language_HTML'] = LanguageCode::bcp47( $params['lemma_language'] );

		return ( new TemplateParser( __DIR__ ) )->processTemplate(
			'SpecialNewLexeme-infopanel',
			$staticTemplateParams + $params
		);
	}

	/**
	 * Get the suggested lexical category items with their labels and descriptions.
	 *
	 * @return array[]
	 */
	private function getLexicalCategorySuggestions(): array {
		$itemIds = array_map(
			[ $this->entityIdParser, 'parse' ],
			$this->getConfig()->get( 'LexemeLexicalCategoryItemIds' )
		);
		$labelDescriptionLookup = $this->labelDescriptionLookupFactory->newLabelDescriptionLookup(
			$this->getLanguage(),
			$itemIds, // prefetch labels and descriptions of all these item IDs
			[ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION ]
		);

		return array_map( static function ( EntityId $entityId ) use ( $labelDescriptionLookup ) {
			$label = $labelDescriptionLookup->getLabel( $entityId );
			$description = $labelDescriptionLookup->getDescription( $entityId );
			$suggestion = [
				'id' => $entityId->getSerialization(),
				'display' => [],
			];
			if ( $label !== null ) {
				$suggestion['display']['label'] = self::termToArrayForJs( $label );
			}
			if ( $description !== null ) {
				$suggestion['display']['description'] = self::termToArrayForJs( $description );
			}
			return $suggestion;
		}, $itemIds );
	}

	private static function termToArrayForJs( TermFallback $term ): array {
		return [
			'language' => LanguageCode::bcp47( $term->getActualLanguageCode() ),
			'value' => $term->getText(),
		];
	}

	private function createForm( array $exampleLexemeParams ): HTMLForm {
		return HTMLForm::factory( 'ooui', $this->getFormFields( $exampleLexemeParams ), $this->getContext() )
			->setSubmitCallback(
				function ( $data, HTMLForm $form ) {
					// $data is already validated at this point (according to the field definitions)

					$entity = $this->createEntityFromFormData( $data );

					$summary = $this->createSummary( $entity );

					$saveStatus = $this->saveEntity(
						$entity,
						$summary,
						$form->getRequest()->getVal( 'wpEditToken' )
					);

					if ( !$saveStatus->isGood() ) {
						return $saveStatus;
					}

					$metric = $this->statsFactory->getCounter( 'special_new_lexeme_nojs_create_total' );
					$metric->copyToStatsdAt( 'wikibase.lexeme.special.NewLexeme.nojs.create' )->increment();

					return $saveStatus;
				}
			)->addPreHtml( '<noscript>' )
			->addPostHtml( '</noscript>' );
	}

	private function createEntityFromFormData( array $formData ): Lexeme {
		$entity = new Lexeme();
		$lemmaLanguage = $formData[self::FIELD_LEMMA_LANGUAGE];

		$lemmas = new TermList( [ new Term( $lemmaLanguage, $formData[self::FIELD_LEMMA] ) ] );
		$entity->setLemmas( $lemmas );

		$entity->setLexicalCategory( new ItemId( $formData[self::FIELD_LEXICAL_CATEGORY] ) );

		$entity->setLanguage( new ItemId( $formData[self::FIELD_LEXEME_LANGUAGE] ) );

		return $entity;
	}

	private function createSummary( Lexeme $lexeme ): Summary {
		$uiLanguageCode = $this->getLanguage()->getCode();

		$summary = new Summary( 'wbeditentity', 'create' );
		$summary->setLanguage( $uiLanguageCode );

		$lemmaIterator = $lexeme->getLemmas()->getIterator();
		/** @var Term|null $lemmaTerm */
		$lemmaTerm = $lemmaIterator->current();
		$summary->addAutoSummaryArgs( $lemmaTerm->getText() );

		return $summary;
	}

	private function redirectToEntityPage( EditEntityStatus $status ) {
		$entity = $status->getRevision()->getEntity();
		$title = $this->entityTitleLookup->getTitleForId( $entity->getId() );
		$savedTempUser = $status->getSavedTempUser();
		$redirectUrl = '';
		if ( $savedTempUser !== null ) {
			$this->getHookRunner()->onTempUserCreatedRedirect(
				$this->getRequest()->getSession(),
				$savedTempUser,
				$title->getPrefixedDBkey(),
				'',
				'',
				$redirectUrl
			);
		}
		if ( !$redirectUrl ) {
			$redirectUrl = $title->getFullURL();
		}
		$this->getOutput()->redirect( $redirectUrl );
	}

	private function newEditEntity(): EditEntity {
		return $this->editEntityFactory->newEditEntity(
			$this->getContext(),
			null,
			0,
			$this->getRequest()->wasPosted()
		);
	}

	private function saveEntity(
		EntityDocument $entity,
		FormatableSummary $summary,
		string $token
	): EditEntityStatus {
		return $this->newEditEntity()->attemptSave(
			$entity,
			$this->summaryFormatter->formatSummary( $summary ),
			EDIT_NEW,
			$token,
			null,
			$this->tags
		);
	}

	private function getFormFields( array $exampleLexemeParams ): array {
		return [
			self::FIELD_LEMMA => [
				'name' => self::FIELD_LEMMA,
				'class' => HTMLTrimmedTextField::class,
				'id' => 'wb-newlexeme-lemma',
				'required' => true,
				'placeholder-message' => [
					'wikibaselexeme-newlexeme-lemma-placeholder-with-example',
					Message::plaintextParam( $exampleLexemeParams['lemma_text'] ),
				],
				'label-message' => 'wikibaselexeme-newlexeme-lemma',
				'validation-callback' => function ( string $lemma ) {
					$result = $this->lemmaTermValidator->validate( $lemma );
					return $result->isValid() ?:
						$this->validatorErrorLocalizer->getErrorMessage( $result->getErrors()[0] );
				},
			],
			self::FIELD_LEMMA_LANGUAGE => [
				'name' => self::FIELD_LEMMA_LANGUAGE,
				'class' => LemmaLanguageField::class,
				'cssclass' => 'lemma-language',
				'id' => 'wb-newlexeme-lemma-language',
				'label-message' => 'wikibaselexeme-newlexeme-lemma-language',
				'placeholder-message' => [
					'wikibaselexeme-newlexeme-lemma-language-placeholder-with-example',
					Message::plaintextParam( $exampleLexemeParams['lemma_language'] ),
				],
			],
			self::FIELD_LEXEME_LANGUAGE => [
				'name' => self::FIELD_LEXEME_LANGUAGE,
				'labelFieldName' => self::FIELD_LEXEME_LANGUAGE . '-label',
				'class' => HTMLItemReferenceField::class,
				'id' => 'wb-newlexeme-lexeme-language',
				'label-message' => 'wikibaselexeme-newlexeme-language',
				'required' => true,
				'placeholder-message' => [
					'wikibaselexeme-newlexeme-language-placeholder-with-example',
					Message::plaintextParam( $exampleLexemeParams['language_item_id'] ),
				],
			],
			self::FIELD_LEXICAL_CATEGORY => [
				'name' => self::FIELD_LEXICAL_CATEGORY,
				'labelFieldName' => self::FIELD_LEXICAL_CATEGORY . '-label',
				'class' => HTMLItemReferenceField::class,
				'id' => 'wb-newlexeme-lexicalCategory',
				'label-message' => 'wikibaselexeme-newlexeme-lexicalcategory',
				'required' => true,
				'placeholder-message' => [
					'wikibaselexeme-newlexeme-lexicalcategory-placeholder-with-example',
					Message::plaintextParam( $exampleLexemeParams['lexical_category_item_id'] ),
				],
			],
			'copyright-message' => [
				'name' => 'copyright-message',
				'type' => 'info',
				'raw' => true,
				'id' => 'wb-newlexeme-copyright',
				'default' => $this->getCopyrightHTML(),
			],
		];
	}

	public function setHeaders(): void {
		$out = $this->getOutput();
		$out->setPageTitleMsg( $this->getDescription() );
	}

	/** @see \Wikibase\Repo\Specials\SpecialWikibasePage::getGroupName() */
	protected function getGroupName(): string {
		return 'wikibase';
	}

	public function getDescription(): Message {
		return $this->msg( 'special-newlexeme' );
	}

	/**
	 * @throws UserBlockedError
	 */
	private function checkBlocked(): void {
		$block = $this->getUser()->getBlock();
		if ( $block && $block->isSitewide() ) {
			throw new UserBlockedError( $block );
		}
	}

	/**
	 * @throws UserBlockedError
	 */
	private function checkBlockedOnNamespace(): void {
		$namespace = $this->entityNamespaceLookup->getEntityNamespace( Lexeme::ENTITY_TYPE );
		$block = $this->getUser()->getBlock();
		if ( $block && $block->appliesToNamespace( $namespace ) ) {
			throw new UserBlockedError( $block );
		}
	}

	/**
	 * @return string HTML
	 */
	private function getCopyrightHTML() {
		return $this->copyrightView->getHtml(
			$this->getLanguage(),
			'wikibaselexeme-newlexeme-submit'
		);
	}

	private function showAnonymousEditWarning(): void {
		if ( !$this->getUser()->isRegistered() ) {
			$fullTitle = $this->getPageTitle();
			$this->getOutput()->addModuleStyles( 'mediawiki.codex.messagebox.styles' );
			$this->getOutput()->addHTML(
				Html::rawElement(
					'noscript',
					[],
					Html::warningBox(
						$this->msg( $this->anonymousEditWarningBuilder->buildAnonymousEditWarningMessage( $fullTitle ) )
							->parse(),
						'wbl-snl-anonymous-edit-warning-no-js'
					)
				)
			);
		}
	}
}
