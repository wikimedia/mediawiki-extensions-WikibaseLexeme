<?php
declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Specials;

use HTMLForm;
use Iterator;
use SpecialPage;
use Status;
use UserBlockedError;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
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
	private $editEntityFactory;
	private $entityNamespaceLookup;
	private $entityTitleLookup;
	private $summaryFormatter;

	public function __construct(
		array $tags,
		MediawikiEditEntityFactory $editEntityFactory,
		EntityNamespaceLookup $entityNamespaceLookup,
		EntityTitleStoreLookup $entityTitleLookup,
		SummaryFormatter $summaryFormatter
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
		$this->editEntityFactory = $editEntityFactory;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->summaryFormatter = $summaryFormatter;
	}

	public static function factory(
		MediawikiEditEntityFactory $editEntityFactory,
		EntityNamespaceLookup $entityNamespaceLookup,
		EntityTitleStoreLookup $entityTitleLookup,
		SettingsArray $repoSettings,
		SummaryFormatter $summaryFormatter
	): self {
		return new self(
			$repoSettings->getSetting( 'specialPageTags' ),
			$editEntityFactory,
			$entityNamespaceLookup,
			$entityTitleLookup,
			$summaryFormatter
		);
	}

	public function doesWrites(): bool {
		return true;
	}

	/**
	 * @param string|null $subPage
	 */
	public function execute( $subPage ): void {
		parent::execute( $subPage );

		$this->checkBlocked();
		$this->checkBlockedOnNamespace();
		$this->checkReadOnly();

		$output = $this->getOutput();
		$this->setHeaders();

		$output->addHTML( '<div id="special-newlexeme-root"></div>' );
		$output->addModules( [ 'wikibase.lexeme.special.NewLexemeAlpha' ] );

		$form = $this->createForm();

		// handle submit (submit callback may create form, see below)
		// or show form (possibly with errors); status represents submit result
		$status = $form->show();

		if ( $status instanceof Status && $status->isGood() ) {
			$this->redirectToEntityPage( $status->getValue() );
		}
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
				'label-message' => 'wikibaselexeme-newlexeme-lemma',
				'validation-callback' => function ( string $lemma ) {
					// TODO use LemmaTermValidator with ValidatorErrorLocalizer instead
					if ( mb_strlen( $lemma ) > 1000 ) {
						return $this->msg( 'wikibase-validator-too-long' )
							->numParams( 1000 );
					}
					return true;
				},
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
