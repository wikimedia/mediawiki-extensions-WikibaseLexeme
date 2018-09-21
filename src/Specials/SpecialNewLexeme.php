<?php

namespace Wikibase\Lexeme\Specials;

use Html;
use HTMLForm;
use Iterator;
use OutputPage;
use SpecialPage;
use Status;
use UserBlockedError;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\EditEntity;
use Wikibase\EditEntityFactory;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Specials\HTMLForm\ItemSelectorWidgetField;
use Wikibase\Lexeme\Specials\HTMLForm\LemmaLanguageField;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Specials\HTMLForm\HTMLTrimmedTextField;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;
use Wikimedia\Assert\Assert;

/**
 * Page for creating new Lexeme entities.
 *
 * @license GPL-2.0-or-later
 */
class SpecialNewLexeme extends SpecialPage {

	/* public */ const FIELD_LEXEME_LANGUAGE = 'lexeme-language';
	/* public */ const FIELD_LEXICAL_CATEGORY = 'lexicalcategory';
	/* public */ const FIELD_LEMMA = 'lemma';
	/* public */ const FIELD_LEMMA_LANGUAGE = 'lemma-language';

	private $copyrightView;
	private $entityNamespaceLookup;
	private $summaryFormatter;
	private $entityTitleLookup;
	private $editEntityFactory;

	public function __construct(
		SpecialPageCopyrightView $copyrightView,
		EntityNamespaceLookup $entityNamespaceLookup,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		EditEntityFactory $editEntityFactory
	) {
		parent::__construct(
			'NewLexeme',
			'createpage'
		);

		$this->copyrightView = $copyrightView;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->summaryFormatter = $summaryFormatter;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->editEntityFactory = $editEntityFactory;
	}

	public static function newFromGlobalState(): self {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		return new self(
			$copyrightView,
			$wikibaseRepo->getEntityNamespaceLookup(),
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory()
		);
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->checkBlocked();
		$this->checkReadOnly();

		$form = $this->createForm();

		$form->prepareForm();

		/** @var Status|false $submitStatus `false` if form was not submitted */
		$submitStatus = $form->tryAuthorizedSubmit();

		if ( $submitStatus && $submitStatus->isGood() ) {
			$this->redirectToEntityPage( $submitStatus->getValue() );
			return;
		}

		$out = $this->getOutput();

		$this->displayBeforeForm( $out );

		$form->displayForm( $submitStatus ?: Status::newGood() );
	}

	private function checkBlocked() {
		if ( $this->getUser()->isBlocked() ) {
			throw new UserBlockedError( $this->getUser()->getBlock() );
		}
	}

	/**
	 * @return HTMLForm
	 */
	private function createForm() {
		return HTMLForm::factory( 'ooui', $this->getFormFields(), $this->getContext() )
			->setId( 'mw-newentity-form1' )
			->setSubmitID( 'wb-newentity-submit' )
			->setSubmitName( 'submit' )
			->setSubmitTextMsg( 'wikibase-newentity-submit' )
			->setWrapperLegendMsg( $this->getLegend() )
			->setSubmitCallback(
				function ( $data, HTMLForm $form ) {
					// TODO: no form data validation??

					$entity = $this->createEntityFromFormData( $data );

					$summary = $this->createSummary( $entity );

					$saveStatus = $this->saveEntity(
						$entity,
						$summary,
						$form->getRequest()->getVal( 'wpEditToken' ),
						EDIT_NEW
					);

					if ( !$saveStatus->isGood() ) {
						return $saveStatus;
					}

					return Status::newGood( $entity );
				}
			);
	}

	private function newEditEntity(): EditEntity {
		return $this->editEntityFactory->newEditEntity(
			$this->getUser(),
			null,
			0,
			$this->getRequest()->wasPosted()
		);
	}

	/**
	 * Saves the entity using the given summary.
	 *
	 * @note Call prepareEditEntity() first.
	 *
	 * @param EntityDocument $entity
	 * @param FormatableSummary $summary
	 * @param string $token
	 * @param int $flags The edit flags (see WikiPage::doEditContent)
	 *
	 * @return Status
	 */
	private function saveEntity(
		EntityDocument $entity,
		FormatableSummary $summary,
		$token,
		$flags
	): Status {
		return $this->newEditEntity()->attemptSave(
			$entity,
			$this->summaryFormatter->formatSummary( $summary ),
			$flags,
			$token
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
			],
			self::FIELD_LEXEME_LANGUAGE => [
				'name' => self::FIELD_LEXEME_LANGUAGE,
				'labelFieldName' => self::FIELD_LEXEME_LANGUAGE . '-label',
				'class' => ItemSelectorWidgetField::class,
				'id' => 'wb-newlexeme-lexeme-language',
				'label-message' => 'wikibaselexeme-newlexeme-language',
				'required' => true,
				'placeholder-message' => 'wikibaselexeme-newlexeme-language-placeholder'
			],
			self::FIELD_LEXICAL_CATEGORY => [
				'name' => self::FIELD_LEXICAL_CATEGORY,
				'labelFieldName' => self::FIELD_LEXICAL_CATEGORY . '-label',
				'class' => ItemSelectorWidgetField::class,
				'id' => 'wb-newlexeme-lexicalCategory',
				'label-message' => 'wikibaselexeme-newlexeme-lexicalcategory',
				'required' => true,
				'placeholder-message' => 'wikibaselexeme-newlexeme-lexicalcategory-placeholder'
			]
		];
	}

	private function getLegend() {
		return $this->msg( 'wikibaselexeme-newlexeme-fieldset' );
	}

	private function createEntityFromFormData( array $formData ): Lexeme {
		$entity = new Lexeme();
		$lemmaLanguage = $formData[ self::FIELD_LEMMA_LANGUAGE ];

		$lemmas = new TermList( [ new Term( $lemmaLanguage, $formData[ self::FIELD_LEMMA ] ) ] );
		$entity->setLemmas( $lemmas );

		if ( !empty( $formData[ self::FIELD_LEXICAL_CATEGORY ] ) ) {
			$entity->setLexicalCategory( new ItemId( $formData[ self::FIELD_LEXICAL_CATEGORY ] ) );
		}

		if ( !empty( $formData[ self::FIELD_LEXEME_LANGUAGE ] ) ) {
			$entity->setLanguage( new ItemId( $formData[ self::FIELD_LEXEME_LANGUAGE ] ) );
		}

		return $entity;
	}

	/**
	 * @param Lexeme $lexeme
	 *
	 * @return Summary
	 */
	private function createSummary( EntityDocument $lexeme ): Summary {
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

	private function displayBeforeForm( OutputPage $output ) {
		$output->addModules( 'wikibase.special.newEntity' );

		$output->addHTML( $this->getCopyrightHTML() );

		foreach ( $this->getWarnings() as $warning ) {
			$output->addHTML( Html::rawElement( 'div', [ 'class' => 'warning' ], $warning ) );
		}

		$output->addModules( 'wikibase.lexeme.special.NewLexeme' );
		$output->addModuleStyles( 'wikibase.lexeme.special.NewLexeme.styles' );
	}

	/**
	 * @return string HTML
	 */
	private function getCopyrightHTML() {
		return $this->copyrightView->getHtml(
			$this->getLanguage(),
			'wikibase-newentity-submit'
		);
	}

	private function getWarnings(): array {
		if ( $this->getUser()->isAnon() ) {
			return [
				$this->msg(
					'wikibase-anonymouseditwarning',
					$this->msg( 'wikibase-entity-lexeme' )
				),
			];
		}

		return [];
	}

	/**
	 * @see SpecialPage::doesWrites
	 *
	 * @return bool
	 */
	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialPage::isListed()
	 */
	public function isListed() {
		return $this->entityNamespaceLookup->getEntityNamespace( Lexeme::ENTITY_TYPE ) !== null;
	}

	protected function getGroupName() {
		return 'wikibase';
	}

	public function getDescription() {
		return $this->msg( 'special-newlexeme' )->text();
	}

	public function setHeaders() {
		$out = $this->getOutput();
		$out->setArticleRelated( false );
		$out->setPageTitle( $this->getDescription() );
	}

	public function outputHeader( $summaryMessageKey = '' ) {
		parent::outputHeader( 'wikibase-newlexeme-summary' );
	}

}
