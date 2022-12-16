<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Content;

use Article;
use IContextSource;
use Psr\Container\ContainerInterface;
use Title;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\DataAccess\Store\LemmaLookup;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\LexemeSubEntityId;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\MediaWiki\Actions\LexemeHistoryAction;
use Wikibase\Lexeme\MediaWiki\Actions\ViewLexemeAction;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\Actions\EditEntityAction;
use Wikibase\Repo\Actions\SubmitEntityAction;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Content\EntityHolder;
use Wikibase\Repo\Content\EntityInstanceHolder;
use Wikibase\Repo\Search\Fields\FieldDefinitions;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeHandler extends EntityHandler {

	private EntityIdLookup $entityIdLookup;

	private EntityLookup $entityLookup;

	private LemmaLookup $lemmaLookup;
	private LexemeTermFormatter $lexemeTermFormatter;

	public function __construct(
		EntityContentDataCodec $contentCodec,
		EntityConstraintProvider $constraintProvider,
		ValidatorErrorLocalizer $errorLocalizer,
		EntityIdParser $entityIdParser,
		EntityIdLookup $entityIdLookup,
		EntityLookup $entityLookup,
		FieldDefinitions $lexemeFieldDefinitions,
		LemmaLookup $lemmaLookup,
		LexemeTermFormatter $lexemeTermFormatter,
		callable $legacyExportFormatDetector = null
	) {
		parent::__construct(
			LexemeContent::CONTENT_MODEL_ID,
			null, // TODO: this is unused in the parent class and has a TODO to be removed
			$contentCodec,
			$constraintProvider,
			$errorLocalizer,
			$entityIdParser,
			$lexemeFieldDefinitions,
			$legacyExportFormatDetector
		);

		$this->entityIdLookup = $entityIdLookup;
		$this->entityLookup = $entityLookup;
		$this->lemmaLookup = $lemmaLookup;
		$this->lexemeTermFormatter = $lexemeTermFormatter;
	}

	/**
	 * This is intended to be used in the entity types wiring.
	 */
	public static function factory( ContainerInterface $services, IContextSource $context ): self {
		return new self(
			WikibaseRepo::getEntityContentDataCodec( $services ),
			WikibaseRepo::getEntityConstraintProvider( $services ),
			WikibaseRepo::getValidatorErrorLocalizer( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getEntityIdLookup( $services ),
			WikibaseRepo::getEntityLookup( $services ),
			WikibaseRepo::getFieldDefinitionsFactory( $services )
				->getFieldDefinitionsByType( Lexeme::ENTITY_TYPE ),
			WikibaseLexemeServices::getLemmaLookup( $services ),
			new LexemeTermFormatter(
				$context
					->msg( 'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma' )
					->escaped()
			)
		);
	}

	/**
	 * @see ContentHandler::getActionOverrides
	 */
	public function getActionOverrides(): array {
		return [
			'history' => function (
				Article $article,
				IContextSource $context
			) {
				return new LexemeHistoryAction(
					$article,
					$context,
					$this->entityIdLookup,
					$this->lemmaLookup,
					$this->lexemeTermFormatter
				);
			},
			'view' => ViewLexemeAction::class,
			'edit' => EditEntityAction::class,
			'submit' => SubmitEntityAction::class,
		];
	}

	public function makeEmptyEntity(): Lexeme {
		return new Lexeme();
	}

	public function makeEntityRedirectContent( EntityRedirect $redirect ): LexemeContent {
		$title = $this->getTitleForId( $redirect->getTargetId() );
		return LexemeContent::newFromRedirect( $redirect, $title );
	}

	/** @inheritDoc */
	public function supportsRedirects(): bool {
		return true;
	}

	/**
	 * @see EntityHandler::newEntityContent
	 */
	protected function newEntityContent( EntityHolder $entityHolder = null ): LexemeContent {
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
	 */
	public function makeEntityId( $id ): LexemeId {
		return new LexemeId( $id );
	}

	public function getEntityType(): string {
		return Lexeme::ENTITY_TYPE;
	}

	public function getSpecialPageForCreation(): string {
		return 'NewLexeme';
	}

	public function getIdForTitle( Title $target ): EntityId {
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

	private function normalizeFragmentToId( Title $target ): string {
		$fragment = $target->getFragment();
		if ( strpos( $fragment, LexemeSubEntityId::SUBENTITY_ID_SEPARATOR ) === false ) {
			$fragment = $target->getText() . LexemeSubEntityId::SUBENTITY_ID_SEPARATOR . $fragment;
		}
		return $fragment;
	}

}
