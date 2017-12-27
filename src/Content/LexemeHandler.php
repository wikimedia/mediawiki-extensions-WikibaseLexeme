<?php

namespace Wikibase\Lexeme\Content;

use Article;
use IContextSource;
use Page;
use Wikibase\Content\EntityHolder;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\EditEntityAction;
use Wikibase\HistoryEntityAction;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\Search\LexemeFieldDefinitions;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lexeme\Actions\ViewLexemeAction;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Store\EntityIdLookup;
use Wikibase\SubmitEntityAction;
use Wikibase\TermIndex;
use WikiPage;

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
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $labelLookupFactory;

	/**
	 * @param TermIndex $termIndex
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityConstraintProvider $constraintProvider
	 * @param ValidatorErrorLocalizer $errorLocalizer
	 * @param EntityIdParser $entityIdParser
	 * @param EntityIdLookup $entityIdLookup
	 * @param EntityLookup $entityLookup
	 * @param LanguageFallbackLabelDescriptionLookupFactory $labelLookupFactory
	 * @param LexemeFieldDefinitions $fieldDefinitions
	 * @param callable|null $legacyExportFormatDetector
	 */
	public function __construct(
		TermIndex $termIndex,
		EntityContentDataCodec $contentCodec,
		EntityConstraintProvider $constraintProvider,
		ValidatorErrorLocalizer $errorLocalizer,
		EntityIdParser $entityIdParser,
		EntityIdLookup $entityIdLookup,
		EntityLookup $entityLookup,
		LanguageFallbackLabelDescriptionLookupFactory $labelLookupFactory,
		LexemeFieldDefinitions $fieldDefinitions,
		$legacyExportFormatDetector = null
	) {
		parent::__construct(
			LexemeContent::CONTENT_MODEL_ID,
			$termIndex,
			$contentCodec,
			$constraintProvider,
			$errorLocalizer,
			$entityIdParser,
			$fieldDefinitions,
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
			'history' => function( Page $article, IContextSource $context ) {
				// NOTE: for now, the callback must work with a WikiPage as well as an Article
				// object. Once I0335100b2 is merged, this is no longer needed.
				if ( $article instanceof WikiPage ) {
					$article = Article::newFromWikiPage( $article, $context );
				}

				return new HistoryEntityAction(
					$article,
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

	/**
	 * @see EntityHandler::newEntityContent
	 *
	 * @param EntityHolder|null $entityHolder
	 *
	 * @return LexemeContent
	 */
	protected function newEntityContent( EntityHolder $entityHolder = null ) {
		if ( $entityHolder !== null && $entityHolder->getEntityType() === Form::ENTITY_TYPE ) {
			$lexemeId = $this->getLexemeId( $entityHolder->getEntityId() );
			$entityHolder = new EntityInstanceHolder( $this->entityLookup->getEntity( $lexemeId ) );
		}
		return new LexemeContent( $entityHolder );
	}

	private function getLexemeId( FormId $formId ) {
		$parts = EntityId::splitSerialization( $formId->getLocalPart() );
		$parts = explode( '-', $parts[2], 2 );
		return new LexemeId( $parts[0] );
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

}
