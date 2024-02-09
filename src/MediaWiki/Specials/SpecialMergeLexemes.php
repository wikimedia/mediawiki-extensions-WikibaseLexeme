<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Specials;

use Exception;
use Html;
use HTMLForm;
use InvalidArgumentException;
use MediaWiki\Permissions\PermissionManager;
use Message;
use SpecialPage;
use UserBlockedError;
use Wikibase\Lexeme\Domain\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\AnonymousEditWarningBuilder;
use Wikibase\Repo\Localizer\ExceptionLocalizer;

/**
 * Special page for merging one lexeme into another.
 *
 * @license GPL-2.0-or-later
 */
class SpecialMergeLexemes extends SpecialPage {

	private const FROM_ID = 'from-id';
	private const TO_ID = 'to-id';

	/** @var string[] */
	private array $tags;

	private MergeLexemesInteractor $mergeInteractor;

	private EntityTitleLookup $titleLookup;

	private ExceptionLocalizer $exceptionLocalizer;

	private PermissionManager $permissionManager;

	public function __construct(
		array $tags,
		MergeLexemesInteractor $mergeInteractor,
		EntityTitleLookup $titleLookup,
		ExceptionLocalizer $exceptionLocalizer,
		PermissionManager $permissionManager
	) {
		parent::__construct( 'MergeLexemes', 'item-merge' );
		$this->tags = $tags;
		$this->mergeInteractor = $mergeInteractor;
		$this->titleLookup = $titleLookup;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->permissionManager = $permissionManager;
	}

	/** @inheritDoc */
	public function execute( $subPage ): void {
		$this->setHeaders();
		$this->outputHeader( 'wikibase-mergelexemes-summary' );

		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
		}
		$this->checkBlocked();

		$sourceId = $this->getTextParam( self::FROM_ID );
		$targetId = $this->getTextParam( self::TO_ID );

		if ( $sourceId && $targetId ) {
			$this->mergeLexemes( $sourceId, $targetId );
		}

		$this->showMergeForm();
	}

	public function setHeaders(): void {
		$out = $this->getOutput();
		$out->setArticleRelated( false );
		$out->setPageTitleMsg( $this->getDescription() );
	}

	private function checkBlocked(): void {
		$checkReplica = !$this->getRequest()->wasPosted();
		$userBlock = $this->getUser()->getBlock( $checkReplica );
		if (
			$userBlock !== null &&
			$this->permissionManager->isBlockedFrom(
				$this->getUser(),
				$this->getFullTitle(),
				$checkReplica
			)
		) {
			throw new UserBlockedError( $userBlock );
		}
	}

	public static function factory(
		PermissionManager $permissionManager,
		EntityTitleLookup $entityTitleLookup,
		ExceptionLocalizer $exceptionLocalizer,
		SettingsArray $repoSettings
	): self {
		return new self(
			$repoSettings->getSetting( 'specialPageTags' ),
			WikibaseLexemeServices::newInstance()->newMergeLexemesInteractor(),
			$entityTitleLookup,
			$exceptionLocalizer,
			$permissionManager
		);
	}

	private function showMergeForm(): void {
		HTMLForm::factory( 'ooui', $this->getFormElements(), $this->getContext() )
			->setId( 'wb-mergelexemes' )
			->setPreHtml( $this->anonymousEditWarning() )
			->setHeaderHtml( $this->msg( 'wikibase-lexeme-mergelexemes-intro' )->parse() )
			->setSubmitID( 'wb-mergelexemes-submit' )
			->setSubmitName( 'wikibase-lexeme-mergelexemes-submit' )
			->setSubmitTextMsg( 'wikibase-lexeme-mergelexemes-submit' )
			->setWrapperLegendMsg( 'special-mergelexemes' )
			->setSubmitCallback( static function () {
			} )
			->show();
	}

	private function getFormElements(): array {
		return [
			self::FROM_ID => [
				'name' => self::FROM_ID,
				'default' => $this->getRequest()->getVal( self::FROM_ID ),
				'type' => 'text',
				'id' => 'wb-mergelexemes-from-id',
				'label-message' => 'wikibase-lexeme-mergelexemes-from-id',
			],
			self::TO_ID => [
				'name' => self::TO_ID,
				'default' => $this->getRequest()->getVal( self::TO_ID ),
				'type' => 'text',
				'id' => 'wb-mergelexemes-to-id',
				'label-message' => 'wikibase-lexeme-mergelexemes-to-id',
			],
		];
	}

	private function anonymousEditWarning(): string {
		if ( !$this->getUser()->isRegistered() ) {
			$fullTitle = $this->getPageTitle();
			$anonymousEditWarningBuilder = new AnonymousEditWarningBuilder(
				$this->getSpecialPageFactory()
			);
			return Html::rawElement(
				'p',
				[ 'class' => 'warning' ],
				$anonymousEditWarningBuilder->buildAnonymousEditWarningHTML( $fullTitle->getPrefixedText() )
			);
		}

		return '';
	}

	private function mergeLexemes( $serializedSourceId, $serializedTargetId ): void {
		$sourceId = $this->getLexemeId( $serializedSourceId );
		$targetId = $this->getLexemeId( $serializedTargetId );

		if ( !$sourceId ) {
			$this->showInvalidLexemeIdError( $serializedSourceId );
			return;
		}
		if ( !$targetId ) {
			$this->showInvalidLexemeIdError( $serializedTargetId );
			return;
		}

		// TODO inject interactor+localizer once this is public
		// phpcs:disable MediaWiki.Classes.FullQualifiedClassName.Found
		try {
			\Wikibase\Repo\WikibaseRepo::getTokenCheckInteractor()
				->checkRequestToken( $this->getContext(), 'wpEditToken' );
		} catch ( \Wikibase\Repo\Interactors\TokenCheckException $e ) {
			$message = \Wikibase\Repo\WikibaseRepo::getExceptionLocalizer()
				->getExceptionMessage( $e );
			$this->showErrorHTML( $message->parse() );
			return;
		}
		// phpcs:enable

		try {
			$this->mergeInteractor->mergeLexemes(
				$sourceId,
				$targetId,
				$this->getContext(),
				null,
				false,
				$this->tags
			);
		} catch ( MergingException $e ) {
			$this->showErrorHTML( $e->getErrorMessage()->escaped() );
			return;
		}

		$this->showSuccessMessage( $sourceId, $targetId );
	}

	private function getTextParam( string $name ): string {
		$value = $this->getRequest()->getText( $name, '' );
		return trim( $value );
	}

	/**
	 * @param string $idSerialization
	 *
	 * @return LexemeId|false
	 */
	private function getLexemeId( string $idSerialization ) {
		try {
			return new LexemeId( $idSerialization );
		} catch ( InvalidArgumentException $e ) {
			return false;
		}
	}

	private function showSuccessMessage( LexemeId $sourceId, LexemeId $targetId ): void {
		try {
			$sourceTitle = $this->titleLookup->getTitleForId( $sourceId );
			$targetTitle = $this->titleLookup->getTitleForId( $targetId );
		} catch ( Exception $e ) {
			$this->showErrorHTML( $this->exceptionLocalizer->getExceptionMessage( $e )->escaped() );
			return;
		}

		$this->getOutput()->addWikiMsg(
			'wikibase-lexeme-mergelexemes-success',
			Message::rawParam(
				$this->getLinkRenderer()->makePreloadedLink( $sourceTitle )
			),
			Message::rawParam(
				$this->getLinkRenderer()->makePreloadedLink( $targetTitle )
			)
		);
	}

	private function showInvalidLexemeIdError( $id ): void {
		$this->showErrorHTML(
			( new Message( 'wikibase-lexeme-mergelexemes-error-invalid-id', [ $id ] ) )
				->escaped()
		);
	}

	protected function getGroupName(): string {
		return 'wikibase';
	}

	protected function showErrorHTML( $error ): void {
		$this->getOutput()->addHTML( '<p class="error">' . $error . '</p>' );
	}

	public function getDescription(): Message {
		return $this->msg( 'special-mergelexemes' );
	}

}
