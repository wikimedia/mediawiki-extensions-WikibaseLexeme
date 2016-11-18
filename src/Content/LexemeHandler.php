<?php

namespace Wikibase\Lexeme\Content;

use IContextSource;
use Page;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\EditEntityAction;
use Wikibase\HistoryEntityAction;
use Wikibase\ViewEntityAction;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Store\EntityIdLookup;
use Wikibase\SubmitEntityAction;
use Wikibase\TermIndex;

/**
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeHandler extends EntityHandler {

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;
	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $labelLookupFactory;

	/**
	 * @param EntityPerPage $entityPerPage
	 * @param TermIndex $termIndex
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityConstraintProvider $constraintProvider
	 * @param ValidatorErrorLocalizer $errorLocalizer
	 * @param EntityIdParser $entityIdParser
	 * @param EntityIdLookup $entityIdLookup
	 * @param LanguageFallbackLabelDescriptionLookupFactory $labelLookupFactory
	 * @param callable|null $legacyExportFormatDetector
	 */
	public function __construct(
		EntityPerPage $entityPerPage,
		TermIndex $termIndex,
		EntityContentDataCodec $contentCodec,
		EntityConstraintProvider $constraintProvider,
		ValidatorErrorLocalizer $errorLocalizer,
		EntityIdParser $entityIdParser,
		EntityIdLookup $entityIdLookup,
		LanguageFallbackLabelDescriptionLookupFactory $labelLookupFactory,
		$legacyExportFormatDetector = null
	) {
		parent::__construct(
			LexemeContent::CONTENT_MODEL_ID,
			$entityPerPage,
			$termIndex,
			$contentCodec,
			$constraintProvider,
			$errorLocalizer,
			$entityIdParser,
			$legacyExportFormatDetector
		);
		$this->entityIdLookup = $entityIdLookup;
		$this->labelLookupFactory = $labelLookupFactory;
	}

	/**
	 * @see ContentHandler::getActionOverrides
	 *
	 * @return array
	 */
	public function getActionOverrides() {
		return [
			'history' => function( Page $page, IContextSource $context = null ) {
				return new HistoryEntityAction(
					$page,
					$context,
					$this->entityIdLookup,
					$this->labelLookupFactory->newLabelDescriptionLookup( $context->getLanguage() )
				);
			},
			'view' => ViewEntityAction::class,
			'edit' => EditEntityAction::class,
			'submit' => SubmitEntityAction::class,
		];
	}
	/**
	 * @return string
	 */
	protected function getContentClass() {
		return LexemeContent::class;
	}
	/**
	 * @return Lexeme
	 */
	public function makeEmptyEntity() {
		return new Lexeme();
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

	public function getSpecialPageForCreation() {
		// TODO: Implement getSpecialPageForCreation() method.
	}

}
