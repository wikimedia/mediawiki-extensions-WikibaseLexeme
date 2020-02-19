<?php

namespace Wikibase\Lexeme\MediaWiki\Content;

use IContextSource;
use Title;
use UnexpectedValueException;
use Wikibase\Content\EntityHolder;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\EditEntityAction;
use Wikibase\HistoryEntityAction;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\LexemeSubEntityId;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\MediaWiki\Actions\ViewLexemeAction;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\NullEntityTermStoreWriter;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Search\Fields\FieldDefinitions;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Store\EntityIdLookup;
use Wikibase\SubmitEntityAction;

/**
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeHandler extends EntityHandler {

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $labelLookupFactory;

	/**
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityConstraintProvider $constraintProvider
	 * @param ValidatorErrorLocalizer $errorLocalizer
	 * @param EntityIdParser $entityIdParser
	 * @param EntityIdLookup $entityIdLookup
	 * @param EntityLookup $entityLookup
	 * @param LanguageFallbackLabelDescriptionLookupFactory $labelLookupFactory
	 * @param FieldDefinitions $lexemeFieldDefinitions
	 * @param callable|null $legacyExportFormatDetector
	 */
	public function __construct(
		EntityContentDataCodec $contentCodec,
		EntityConstraintProvider $constraintProvider,
		ValidatorErrorLocalizer $errorLocalizer,
		EntityIdParser $entityIdParser,
		EntityIdLookup $entityIdLookup,
		EntityLookup $entityLookup,
		LanguageFallbackLabelDescriptionLookupFactory $labelLookupFactory,
		FieldDefinitions $lexemeFieldDefinitions,
		$legacyExportFormatDetector = null
	) {
		parent::__construct(
			LexemeContent::CONTENT_MODEL_ID,
			new NullEntityTermStoreWriter(),
			$contentCodec,
			$constraintProvider,
			$errorLocalizer,
			$entityIdParser,
			$lexemeFieldDefinitions,
			$legacyExportFormatDetector
		);

		$this->entityIdLookup = $entityIdLookup;
		$this->entityLookup = $entityLookup;
		$this->labelLookupFactory = $labelLookupFactory;
	}

	/**
	 * @see ContentHandler::getActionOverrides
	 *
	 * @return array
	 */
	public function getActionOverrides() {
		return [
			'history' => function( object $page, IContextSource $context ) {
				/** @var \WikiPage|\Article $page */
				return new HistoryEntityAction(
					$page,
					$context,
					$this->entityIdLookup,
					$this->labelLookupFactory->newLabelDescriptionLookup( $context->getLanguage() )
				);
			},
			'view' => ViewLexemeAction::class,
			'edit' => EditEntityAction::class,
			'submit' => SubmitEntityAction::class,
		];
	}

	/**
	 * @return Lexeme
	 */
	public function makeEmptyEntity() {
		return new Lexeme();
	}

	public function makeEntityRedirectContent( EntityRedirect $redirect ) {
		$title = $this->getTitleForId( $redirect->getTargetId() );
		return LexemeContent::newFromRedirect( $redirect, $title );
	}

	/**
	 * @see EntityHandler::supportsRedirects()
	 */
	public function supportsRedirects() {
		return true;
	}

	/**
	 * @see EntityHandler::newEntityContent
	 *
	 * @param EntityHolder|null $entityHolder
	 *
	 * @return LexemeContent
	 */
	protected function newEntityContent( EntityHolder $entityHolder = null ) {
		if ( $entityHolder !== null && $entityHolder->getEntityType() === Form::ENTITY_TYPE ) {
			$formId = $entityHolder->getEntityId();
			if ( !( $formId instanceof FormId ) ) {
				throw new UnexpectedValueException( '$formId must be a FormId' );
			}
			$lexemeId = $formId->getLexemeId();
			$entityHolder = new EntityInstanceHolder( $this->entityLookup->getEntity( $lexemeId ) );
		}
		if ( $entityHolder !== null && $entityHolder->getEntityType() === Sense::ENTITY_TYPE ) {
			$senseId = $entityHolder->getEntityId();
			if ( !( $senseId instanceof SenseId ) ) {
				throw new UnexpectedValueException( '$senseId must be a SenseId' );
			}
			$lexemeId = $senseId->getLexemeId();
			$entityHolder = new EntityInstanceHolder( $this->entityLookup->getEntity( $lexemeId ) );
		}
		return new LexemeContent( $entityHolder );
	}

	/**
	 * @param string $id
	 *
	 * @return LexemeId
	 */
	public function makeEntityId( $id ) {
		return new LexemeId( $id );
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return Lexeme::ENTITY_TYPE;
	}

	/**
	 * @return string
	 */
	public function getSpecialPageForCreation() {
		return 'NewLexeme';
	}

	public function getIdForTitle( Title $target ) {
		$lexemeId = parent::getIdForTitle( $target );

		if ( $target->hasFragment() ) {
			$id = $this->normalizeFragmentToId( $target );
			// TODO use an EntityIdParser (but parent's $this->entityIdParser is currently private)
			if ( preg_match( FormId::PATTERN, $id ) ) {
				$lexemeSubEntityId = new FormId( $id );
			}
			if ( preg_match( SenseId::PATTERN, $id ) ) {
				$lexemeSubEntityId = new SenseId( $id );
			}

			if (
				isset( $lexemeSubEntityId ) &&
				$lexemeSubEntityId->getLexemeId()->equals( $lexemeId )
			) {
				return $lexemeSubEntityId;
			}
		}

		return $lexemeId;
	}

	private function normalizeFragmentToId( Title $target ) {
		$fragment = $target->getFragment();
		if ( strpos( $fragment, LexemeSubEntityId::SUBENTITY_ID_SEPARATOR ) === false ) {
			$fragment = $target->getText() . LexemeSubEntityId::SUBENTITY_ID_SEPARATOR . $fragment;
		}
		return $fragment;
	}

}
