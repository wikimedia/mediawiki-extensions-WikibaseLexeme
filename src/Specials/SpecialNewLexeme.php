<?php

namespace Wikibase\Lexeme\Specials;

use Status;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Repo\Specials\HTMLForm\HTMLContentLanguageField;
use Wikibase\Repo\Specials\HTMLForm\HTMLTrimmedTextField;
use Wikibase\Repo\Specials\SpecialNewEntity;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Summary;

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

	public function __construct() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		parent::__construct( 'NewLexeme', 'createpage', $copyrightView );
	}

	/**
	 * @return array[]
	 */
	protected function getFormFields() {
		return [
			self::FIELD_LEMMA_LANGUAGE => [
				'name' => self::FIELD_LEMMA_LANGUAGE,
				'class' => HTMLContentLanguageField::class,
				'id' => 'wb-newlexeme-lemma-language',
				'label-message' => 'wikibase-newlexeme-lemma-language',
			],
			self::FIELD_LEMMA => [
				'name' => self::FIELD_LEMMA,
				'class' => HTMLTrimmedTextField::class,
				'id' => 'wb-newlexeme-lemma',
				'required' => true,
				'placeholder-message' => 'wikibase-lemma-edit-placeholder',
				'label-message' => 'wikibase-newlexeme-lemma'
			],
			self::FIELD_LEXICAL_CATEGORY => [
				'name' => self::FIELD_LEXICAL_CATEGORY,
				'type' => 'text',
				'id' => 'wb-newlexeme-lexicalCategory',
				'placeholder-message' => 'wikibase-lexicalcategory-edit-placeholder',
				'label-message' => 'wikibase-newlexeme-lexicalcategory',
				'validation-callback' => function ( $value ) {
					if ( empty( $value ) ) {
						return true;
					}

					if ( !preg_match( ItemId::PATTERN, $value ) ) {
						// FIXME add text in language files
						return $this->msg( 'invalid format' );
					}

					$entityLookup = WikibaseRepo::getDefaultInstance()->getEntityLookup();
					if ( !$entityLookup->hasEntity( new ItemId( $value ) ) ) {
						// FIXME add text in language files
						return $this->msg( 'does not exist' );
					}

					return true;
				},
			],
			self::FIELD_LEXEME_LANGUAGE => [
				'name' => self::FIELD_LEXEME_LANGUAGE,
				'type' => 'text',
				'id' => 'wb-newlexeme-lexeme-language',
				'placeholder-message' => 'wikibase-language-edit-placeholder',
				'label-message' => 'wikibase-newlexeme-language',
				'validation-callback' => function ( $value ) {
					if ( empty( $value ) ) {
						return true;
					}

					if ( !preg_match( ItemId::PATTERN, $value ) ) {
						// FIXME add text in language files
						return $this->msg( 'invalid format' );
					}

					$entityLookup = WikibaseRepo::getDefaultInstance()->getEntityLookup();
					if ( !$entityLookup->hasEntity( new ItemId( $value ) ) ) {
						// FIXME add text in language files
						return $this->msg( 'does not exist' );
					}

					return true;
				},
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
	protected function createSummary( $lexeme ) {
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

}
