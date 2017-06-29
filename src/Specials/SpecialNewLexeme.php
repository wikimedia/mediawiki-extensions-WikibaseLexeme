<?php

namespace Wikibase\Lexeme\Specials;

use OutputPage;
use Status;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\EditEntityFactory;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Specials\HTMLForm\ItemSelectorWidgetField;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Specials\HTMLForm\HTMLContentLanguageField;
use Wikibase\Repo\Specials\HTMLForm\HTMLTrimmedTextField;
use Wikibase\Repo\Specials\SpecialNewEntity;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Page for creating new Lexeme entities.
 *
 * @license GPL-2.0+
 */
class SpecialNewLexeme extends SpecialNewEntity {

	const FIELD_LEXEME_LANGUAGE = 'lexeme-language';
	const FIELD_LEXICAL_CATEGORY = 'lexicalcategory';
	const FIELD_LEMMA = 'lemma';
	const FIELD_LEMMA_LANGUAGE = 'lemma-language';

	/**
	 * @return self
	 */
	public static function newFromGlobalState() {
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

	public function __construct(
		SpecialPageCopyrightView $copyrightView,
		EntityNamespaceLookup $entityNamespaceLookup,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		EditEntityFactory $editEntityFactory
	) {
		parent::__construct(
			'NewLexeme',
			'createpage',
			$copyrightView,
			$entityNamespaceLookup,
			$summaryFormatter,
			$entityTitleLookup,
			$editEntityFactory
		);
	}

	/**
	 * @see SpecialNewEntity::getEntityType
	 */
	protected function getEntityType() {
		return Lexeme::ENTITY_TYPE;
	}

	/**
	 * @return array[]
	 */
	protected function getFormFields() {
		return [
			self::FIELD_LEMMA => [
				'name' => self::FIELD_LEMMA,
				'class' => HTMLTrimmedTextField::class,
				'id' => 'wb-newlexeme-lemma',
				'required' => true,
				'placeholder-message' => 'wikibase-lemma-edit-placeholder',
				'label-message' => 'wikibase-newlexeme-lemma'
			],
			self::FIELD_LEMMA_LANGUAGE => [
				'name' => self::FIELD_LEMMA_LANGUAGE,
				'class' => HTMLContentLanguageField::class,
				'id' => 'wb-newlexeme-lemma-language',
				'label-message' => 'wikibase-newlexeme-lemma-language',
			],
			self::FIELD_LEXEME_LANGUAGE => [
				'name' => self::FIELD_LEXEME_LANGUAGE,
				'labelFieldName' => self::FIELD_LEXEME_LANGUAGE . '-label',
				'class' => ItemSelectorWidgetField::class,
				'id' => 'wb-newlexeme-lexeme-language',
				'label-message' => 'wikibase-newlexeme-language',
				'required' => true,
			],
			self::FIELD_LEXICAL_CATEGORY => [
				'name' => self::FIELD_LEXICAL_CATEGORY,
				'labelFieldName' => self::FIELD_LEXICAL_CATEGORY . '-label',
				'class' => ItemSelectorWidgetField::class,
				'id' => 'wb-newlexeme-lexicalCategory',
				'label-message' => 'wikibase-newlexeme-lexicalcategory',
				'required' => true,
			]
		];
	}

	/**
	 * @param array $formData
	 *
	 * @return Status
	 */
	protected function validateFormData( array $formData ) {
		return Status::newGood();
	}

	/**
	 * @param array $formData
	 *
	 * @return EntityDocument
	 */
	protected function createEntityFromFormData( array $formData ) {
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
	protected function createSummary( EntityDocument $lexeme ) {
		$uiLanguageCode = $this->getLanguage()->getCode();

		$summary = new Summary( 'wbeditentity', 'create' );
		$summary->setLanguage( $uiLanguageCode );
		/** @var Term|null $lemmaTerm */
		$lemmaTerm = $lexeme->getLemmas()->getIterator()->current();
		$summary->addAutoSummaryArgs( $lemmaTerm->getText() );

		return $summary;
	}

	protected function getLegend() {
		return $this->msg( 'wikibase-newlexeme-fieldset' );
	}

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

	protected function displayBeforeForm( OutputPage $output ) {
		parent::displayBeforeForm( $output );
		$output->addModules( 'wikibase.lexeme.special.NewLexeme' );
	}

}
