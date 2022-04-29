<?php
declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Specials;

use Exception;
use HTMLForm;
use Iterator;
use LanguageCode;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use MediaWiki\Linker\LinkRenderer;
use OOUI\IconWidget;
use SpecialPage;
use Status;
use TemplateParser;
use UserBlockedError;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\MediaWiki\Specials\HTMLForm\LemmaLanguageField;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Summary;
use Wikibase\Repo\EditEntity\EditEntity;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Specials\HTMLForm\HTMLItemReferenceField;
use Wikibase\Repo\Specials\HTMLForm\HTMLTrimmedTextField;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\View\EntityIdFormatterFactory;
use Wikimedia\Assert\Assert;

/**
 * New page for creating new Lexeme entities.
 *
 * @license GPL-2.0-or-later
 */
class SpecialNewLexemeAlpha extends SpecialPage {

	public const FIELD_LEXEME_LANGUAGE = 'lexeme-language';
	public const FIELD_LEXICAL_CATEGORY = 'lexicalcategory';
	public const FIELD_LEMMA = 'lemma';
	public const FIELD_LEMMA_LANGUAGE = 'lemma-language';

	private $tags;
	private $linkRenderer;
	private $statsDataFactory;
	private $editEntityFactory;
	private $entityNamespaceLookup;
	private $entityTitleLookup;
	private $entityLookup;
	private $entityIdParser;
	private $summaryFormatter;
	private $entityIdFormatterFactory;

	public function __construct(
		array $tags,
		LinkRenderer $linkRenderer,
		StatsdDataFactoryInterface $statsDataFactory,
		MediawikiEditEntityFactory $editEntityFactory,
		EntityNamespaceLookup $entityNamespaceLookup,
		EntityTitleStoreLookup $entityTitleLookup,
		EntityLookup $entityLookup,
		EntityIdParser $entityIdParser,
		SummaryFormatter $summaryFormatter,
		EntityIdFormatterFactory $entityIdFormatterFactory
	) {
		parent::__construct(
			'NewLexemeAlpha',
			// We might want to temporarily restrict this page even further,
			// pending product decision.
			'createpage',
			// Unlist this page from Special:SpecialPages.
			false
		);

		$this->tags = $tags;
		$this->linkRenderer = $linkRenderer;
		$this->statsDataFactory = $statsDataFactory;
		$this->editEntityFactory = $editEntityFactory;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityLookup = $entityLookup;
		$this->entityIdParser = $entityIdParser;
		$this->summaryFormatter = $summaryFormatter;
		$this->entityIdFormatterFactory = $entityIdFormatterFactory;
	}

	public static function factory(
		LinkRenderer $linkRenderer,
		StatsdDataFactoryInterface $statsDataFactory,
		MediawikiEditEntityFactory $editEntityFactory,
		EntityNamespaceLookup $entityNamespaceLookup,
		EntityTitleStoreLookup $entityTitleLookup,
		EntityLookup $entityLookup,
		EntityIdParser $entityIdParser,
		SettingsArray $repoSettings,
		SummaryFormatter $summaryFormatter,
		EntityIdFormatterFactory $entityIdFormatterFactory
	): self {
		return new self(
			$repoSettings->getSetting( 'specialPageTags' ),
			$linkRenderer,
			$statsDataFactory,
			$editEntityFactory,
			$entityNamespaceLookup,
			$entityTitleLookup,
			$entityLookup,
			$entityIdParser,
			$summaryFormatter,
			$entityIdFormatterFactory
		);
	}

	public function doesWrites(): bool {
		return true;
	}

	/**
	 * @param string|null $subPage
	 */
	public function execute( $subPage ): void {
		$this->statsDataFactory->increment( 'wikibase.lexeme.special.NewLexeme.views' );

		parent::execute( $subPage );

		$this->checkBlocked();
		$this->checkBlockedOnNamespace();
		$this->checkReadOnly();

		$output = $this->getOutput();
		$this->setHeaders();

		$output->addHTML( '<div id="wbl-snl-intro-text-wrapper"></div>' );
		$output->addHTML( '<div class="wbl-snl-main-content">' );
		$output->addHTML( '<div id="special-newlexeme-root"></div>' );
		$output->addModules( [ 'wikibase.lexeme.special.NewLexemeAlpha' ] );
		$output->addModuleStyles( [ 'wikibase.lexeme.special.NewLexemeAlpha.styles' ] );

		$form = $this->createForm();

		// handle submit (submit callback may create form, see below)
		// or show form (possibly with errors); status represents submit result
		$status = $form->show();

		$output->enableOOUI();
		$output->addModuleStyles( [
			'oojs-ui.styles.icons-content', // info icon
		] );
		$output->addHTML(
			$this->createInfoPanelHtml()
		);
		$output->addHTML( '</div>' ); // .wbl-snl-main-content
		$output->addHTML(
			'<noscript>'
			. '<style type="text/css">#special-newlexeme-root {display:none;}</style>'
			. '</noscript>'
		);

		if ( $status instanceof Status && $status->isGood() ) {
			$this->redirectToEntityPage( $status->getValue() );
		}
	}

	private function createInfoPanelHtml(): string {
		$lexemeIdString = trim( $this->msg( 'wikibaselexeme-newlexeme-info-panel-example-lexeme-id' )->text() );
		try {
			$params = $this->createTemplateParamsFromLexemeId( $lexemeIdString );
		} catch ( Exception $_ ) {
			$params = [
				'lexeme_id_HTML' => 'L1',
				'lemma_text' => 'speak',
				'lemma_language' => 'en',
				'language_link_HTML' => 'English',
				'lexical_category_link_HTML' => 'verb',
			];
		}
		return $this->processInfoPanelTemplate( $params );
	}

	private function createTemplateParamsFromLexemeId( string $lexemeIdString ): array {
		$lexemeId = $this->entityIdParser->parse( $lexemeIdString );
		$lexeme = $this->entityLookup->getEntity( $lexemeId );
		if ( !( $lexeme instanceof Lexeme ) ) {
			throw new Exception( 'Lexeme missing or not a Lexeme' );
		}
		$lemma = $lexeme->getLemmas()->getIterator()->current();
		$entityIdFormatter = $this->entityIdFormatterFactory->getEntityIdFormatter( $this->getLanguage() );
		$lexemeIdLink = $this->linkRenderer->makeKnownLink(
			$this->entityTitleLookup->getTitleForId( $lexemeId ),
			$lexemeIdString
		);
		return [
			'lexeme_id_HTML' => $lexemeIdLink,
			'lemma_text' => $lemma->getText(),
			'lemma_language' => LanguageCode::bcp47( $lemma->getLanguageCode() ),
			'language_link_HTML' => $entityIdFormatter->formatEntityId( $lexeme->getLanguage() ),
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
		return ( new TemplateParser( __DIR__ ) )->processTemplate(
			'SpecialNewLexemeAlpha-infopanel',
			$staticTemplateParams + $params
		);
	}

	private function createForm(): HTMLForm {
		return HTMLForm::factory( 'ooui', $this->getFormFields(), $this->getContext() )
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

					return Status::newGood( $entity );
				}
			)->addPreHtml( '<noscript>' )->addPostHtml( '</noscript>' );
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
		// As getIterator can also in theory return a Traversable, guard against that
		Assert::invariant(
			$lemmaIterator instanceof Iterator,
			'TermList::getIterator did not return an instance of Iterator'
		);
		/** @var Term|null $lemmaTerm */
		$lemmaTerm = $lemmaIterator->current();
		$summary->addAutoSummaryArgs( $lemmaTerm->getText() );

		return $summary;
	}

	private function redirectToEntityPage( EntityDocument $entity ) {
		$this->getOutput()->redirect(
			$this->entityTitleLookup->getTitleForId( $entity->getId() )->getFullURL()
		);
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
	): Status {
		return $this->newEditEntity()->attemptSave(
			$entity,
			$this->summaryFormatter->formatSummary( $summary ),
			EDIT_NEW,
			$token,
			null,
			$this->tags
		);
	}

	private function getFormFields(): array {
		return [
			self::FIELD_LEMMA => [
				'name' => self::FIELD_LEMMA,
				'class' => HTMLTrimmedTextField::class,
				'id' => 'wb-newlexeme-lemma',
				'required' => true,
				'placeholder-message' => 'wikibaselexeme-lemma-edit-placeholder',
				'label-message' => 'wikibaselexeme-newlexeme-lemma'
			],
			self::FIELD_LEMMA_LANGUAGE => [
				'name' => self::FIELD_LEMMA_LANGUAGE,
				'class' => LemmaLanguageField::class,
				'cssclass' => 'lemma-language',
				'id' => 'wb-newlexeme-lemma-language',
				'label-message' => 'wikibaselexeme-newlexeme-lemma-language',
				'placeholder-message' => 'wikibaselexeme-newlexeme-lemma-language-placeholder',
			],
			self::FIELD_LEXEME_LANGUAGE => [
				'name' => self::FIELD_LEXEME_LANGUAGE,
				'labelFieldName' => self::FIELD_LEXEME_LANGUAGE . '-label',
				'class' => HTMLItemReferenceField::class,
				'id' => 'wb-newlexeme-lexeme-language',
				'label-message' => 'wikibaselexeme-newlexeme-language',
				'required' => true,
				'placeholder-message' => 'wikibaselexeme-newlexeme-language-placeholder'
			],
			self::FIELD_LEXICAL_CATEGORY => [
				'name' => self::FIELD_LEXICAL_CATEGORY,
				'labelFieldName' => self::FIELD_LEXICAL_CATEGORY . '-label',
				'class' => HTMLItemReferenceField::class,
				'id' => 'wb-newlexeme-lexicalCategory',
				'label-message' => 'wikibaselexeme-newlexeme-lexicalcategory',
				'required' => true,
				'placeholder-message' => 'wikibaselexeme-newlexeme-lexicalcategory-placeholder'
			]
		];
	}

	public function setHeaders(): void {
		$out = $this->getOutput();
		$out->setPageTitle( $this->getDescription() );
	}

	public function getDescription(): string {
		return $this->msg( 'special-newlexeme-alpha' )->text();
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
}
