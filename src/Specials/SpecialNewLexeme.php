<?php

namespace Wikibase\Lexeme\Specials;

use Html;
use HTMLForm;
use InvalidArgumentException;
use Status;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Repo\Specials\SpecialWikibaseRepoPage;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Summary;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikimedia\Assert\Assert;

/**
 * Page for creating new Lexeme entities that contain a Fingerprint.
 * Mostly copied from SpecialNewEntity
 *
 * @license GPL-2.0+
 */
class SpecialNewLexeme extends SpecialWikibaseRepoPage {

	/**
	 * Contains pieces of the sub-page name of this special page if a subpage was called.
	 * E.g. [ 'a', 'b' ] in case of 'Special:NewLexeme/a/b'
	 * @var string[]|null
	 */
	protected $parts = null;

	/**
	 * @var string|null
	 */
	private $lemma;

	/**
	 * @var string
	 */
	private $contentLanguageCode;

	/**
	 * @var string[]
	 */
	private $languageCodes;

	/**
	 * @var SpecialPageCopyrightView
	 */
	private $copyrightView;

	/**
	 * @var LanguageDirectionalityLookup
	 */
	private $languageDirectionalityLookup;

	/**
	 * @var string
	 */
	private $lexicalCategory;

	/**
	 * @var string
	 */
	private $language;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	public function __construct() {
		parent::__construct( 'NewLexeme', 'createpage' );
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$this->copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);
		$this->languageCodes = $wikibaseRepo->getTermsLanguages()->getLanguages();
		$this->languageDirectionalityLookup = $wikibaseRepo->getLanguageDirectionalityLookup();
		$this->languageNameLookup = $wikibaseRepo->getLanguageNameLookup();
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->checkPermissions();
		$this->checkBlocked();
		$this->checkReadOnly();

		$this->parts = ( $subPage === '' ? [] : explode( '/', $subPage ) );
		$this->prepareArguments();

		$out = $this->getOutput();

		$uiLanguageCode = $this->getLanguage()->getCode();

		if ( $this->getRequest()->wasPosted()
		     && $this->getUser()->matchEditToken( $this->getRequest()->getVal( 'wpEditToken' ) )
		) {
			if ( $this->hasSufficientArguments() ) {
				$entity = $this->createEntity();

				$status = $this->modifyEntity( $entity );

				if ( $status->isGood() ) {
					$summary = new Summary( 'wbeditentity', 'create' );
					$summary->setLanguage( $uiLanguageCode );
					$summary->addAutoSummaryArgs( $this->lemma );

					$status = $this->saveEntity(
						$entity,
						$summary,
						$this->getRequest()->getVal( 'wpEditToken' ),
						EDIT_NEW
					);
					var_dump( 'can be saved' );
					$out = $this->getOutput();

					if ( !$status->isOK() ) {
						var_dump( 'here' );
						$out->addHTML( '<div class="error">' );
						$out->addWikiText( $status->getWikiText() );
						$out->addHTML( '</div>' );
					} elseif ( $entity !== null ) {
						$title = $this->getEntityTitle( $entity->getId() );
						$entityUrl = $title->getFullURL();
						$this->getOutput()->redirect( $entityUrl );
					}
				} else {
					$out->addHTML( '<div class="error">' );
					$out->addHTML( $status->getHTML() );
					$out->addHTML( '</div>' );
				}
			}
		}

		$this->getOutput()->addModuleStyles( [ 'wikibase.special' ] );

		foreach ( $this->getWarnings() as $warning ) {
			$out->addHTML( Html::element( 'div', [ 'class' => 'warning' ], $warning ) );
		}

		$this->createForm( $this->getLegend(), $this->additionalFormElements() );
	}

	/**
	 * Tries to extract argument values from web request or of the page's sub-page parts
	 *
	 * Trimming argument values from web request.
	 */
	protected function prepareArguments() {
		$lemma = $this->getRequest()->getVal(
			'lemma',
			isset( $this->parts[0] ) ? $this->parts[0] : ''
		);
		$this->lemma = $this->stringNormalizer->trimToNFC( $lemma );

		$lexicalCategory = $this->getRequest()->getVal(
			'lexicalcategory',
			isset( $this->parts[0] ) ? $this->parts[0] : ''
		);
		$this->lexicalCategory = $this->stringNormalizer->trimToNFC( $lexicalCategory );

		$language = $this->getRequest()->getVal(
			'lexeme-language',
			isset( $this->parts[0] ) ? $this->parts[0] : ''
		);
		$this->language = $this->stringNormalizer->trimToNFC( $language );

		$this->contentLanguageCode = $this->getRequest()->getVal(
			'lemma-language', $this->getLanguage()->getCode()
		);
	}

	/**
	 * Checks whether required arguments are set sufficiently
	 *
	 * @return bool
	 */
	protected function hasSufficientArguments() {
		return $this->lemma !== '';
	}

	/**
	 * @see SpecialNewEntity::createEntity
	 */
	protected function createEntity() {
		return new Lexeme();
	}

	/**
	 * Attempt to modify entity
	 *
	 * @param EntityDocument &$entity
	 *
	 * @throws InvalidArgumentException
	 * @return Status
	 */
	protected function modifyEntity( EntityDocument &$entity ) {
		/** @var Lexeme $entity */
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		$status = Status::newGood();

		$languageCode = $this->contentLanguageCode;
		if ( !in_array( $languageCode, $this->languageCodes ) ) {
			$status->error( 'wikibase-newitem-not-recognized-language' );
			return $status;
		}

		$lemmas = new TermList( [ new Term( $languageCode, $this->lemma ) ] );
		$entity->setLemmas( $lemmas );

		if ( $this->lexicalCategory !== '' ) {
			$entity->setLexicalCategory( new ItemId( $this->lexicalCategory ) );
		}

		if ( $this->language !== '' ) {
			$entity->setLanguage( new ItemId( $this->language ) );
		}

		return $status;
	}

	/**
	 * Get options for language selector
	 *
	 * @return string[]
	 */
	private function getLanguageOptions() {
		$languageOptions = [];
		foreach ( $this->languageCodes as $code ) {
			$languageName = $this->languageNameLookup->getName( $code );
			$languageOptions["$languageName ($code)"] = $code;
		}
		return $languageOptions;
	}

	/**
	 * @return array[]
	 */
	protected function additionalFormElements() {
		$this->getOutput()->addModules( 'wikibase.special.languageLabelDescriptionAliases' );

		$langCode = $this->contentLanguageCode;
		$langDir = $this->languageDirectionalityLookup->getDirectionality( $this->contentLanguageCode );
		return [
			'lemma-language' => [
				'name' => 'lemma-language',
				'options' => $this->getLanguageOptions(),
				'default' => $langCode,
				'type' => 'combobox',
				'id' => 'wb-newlexeme-lemma-language',
				'label-message' => 'wikibase-newlexeme-language'
			],
			'lemma' => [
				'name' => 'lemma',
				'default' => $this->lemma ?: '',
				'type' => 'text',
				'id' => 'wb-newlexeme-lemma',
				'lang' => $langCode,
				'dir' => $langDir,
				'placeholder' => $this->msg(
					'wikibase-lemma-edit-placeholder'
				)->text(),
				'label-message' => 'wikibase-newlexeme-lemma'
			],
			'lexicalcategory' => [
				'name' => 'lexicalcategory',
				'default' => $this->lexicalCategory,
				'type' => 'text',
				'id' => 'wb-newlexeme-lexicalCategory',
				'lang' => $langCode,
				'dir' => $langDir,
				'placeholder' => $this->msg(
					'wikibase-lexicalcategory-edit-placeholder'
				)->text(),
				'label-message' => 'wikibase-newlexeme-lexicalcategory'
			],
			'lexeme-language' => [
				'name' => 'lexeme-language',
				'default' => $this->language,
				'type' => 'text',
				'id' => 'wb-newlexeme-lexeme-language',
				'lang' => $langCode,
				'dir' => $langDir,
				'placeholder' => $this->msg(
					'wikibase-language-edit-placeholder'
				)->text(),
				'label-message' => 'wikibase-newlexeme-language'
			]
		];
	}

	/**
	 * Building the HTML form for creating a new item.
	 *
	 * @param string|null $legend initial value for the label input box
	 * @param array[] $additionalFormElements initial value for the description input box
	 */
	private function createForm( $legend = null, array $additionalFormElements ) {
		$this->addCopyrightText();

		HTMLForm::factory( 'ooui', $additionalFormElements, $this->getContext() )
			->setId( 'wb-newlexeme-form1' )
			->setSubmitID( 'wb-newlexeme-submit' )
			->setSubmitName( 'submit' )
			->setSubmitTextMsg( 'wikibase-newlexeme-submit' )
			->setWrapperLegendMsg( $legend )
			->setSubmitCallback( function () {// no-op
			} )->show();
	}

	/**
	 * @todo could factor this out into a special page form builder and renderer
	 */
	private function addCopyrightText() {
		$html = $this->copyrightView->getHtml( $this->getLanguage(), 'wikibase-newlexeme-submit' );

		$this->getOutput()->addHTML( $html );
	}

	/**
	 * @see SpecialNewEntity::getLegend()
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-newlexeme-fieldset' );
	}

	/**
	 * @see SpecialCreateEntity::getWarnings
	 *
	 * @return string[]
	 */
	protected function getWarnings() {
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

}
