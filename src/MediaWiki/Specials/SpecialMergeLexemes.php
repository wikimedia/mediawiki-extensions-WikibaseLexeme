<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Specials;

use Exception;
use InvalidArgumentException;
use MediaWiki\Exception\UserBlockedError;
use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Message\Message;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\SpecialPage\SpecialPage;
use Wikibase\Lexeme\Domain\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\AnonymousEditWarningBuilder;
use Wikibase\Repo\Interactors\TokenCheckException;
use Wikibase\Repo\Interactors\TokenCheckInteractor;
use Wikibase\Repo\Localizer\ExceptionLocalizer;

/**
 * Special page for merging one lexeme into another.
 *
 * @license GPL-2.0-or-later
 */
class SpecialMergeLexemes extends SpecialPage {

	private const FROM_ID = 'from-id';
	private const TO_ID = 'to-id';
	private const SUCCESS = 'success';

	/** @var string[] */
	private array $tags;

	private MergeLexemesInteractor $mergeInteractor;

	private TokenCheckInteractor $tokenCheckInteractor;

	private EntityTitleLookup $titleLookup;

	private ExceptionLocalizer $exceptionLocalizer;

	private PermissionManager $permissionManager;

	private AnonymousEditWarningBuilder $anonymousEditWarningBuilder;

	public function __construct(
		array $tags,
		MergeLexemesInteractor $mergeInteractor,
		TokenCheckInteractor $tokenCheckInteractor,
		EntityTitleLookup $titleLookup,
		ExceptionLocalizer $exceptionLocalizer,
		PermissionManager $permissionManager,
		AnonymousEditWarningBuilder $anonymousEditWarningBuilder
	) {
		parent::__construct( 'MergeLexemes', 'item-merge' );
		$this->tags = $tags;
		$this->mergeInteractor = $mergeInteractor;
		$this->tokenCheckInteractor = $tokenCheckInteractor;
		$this->titleLookup = $titleLookup;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->permissionManager = $permissionManager;
		$this->anonymousEditWarningBuilder = $anonymousEditWarningBuilder;
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
			$sourceLexemeId = $this->getLexemeId( $sourceId );
			$targetLexemeId = $this->getLexemeId( $targetId );
			if ( $sourceLexemeId && $targetLexemeId ) {
				if ( $this->getRequest()->getBool( self::SUCCESS ) ) {
					// redirected back here after a successful edit + temp user, show success now
					// (the success may be inaccurate if users created this URL manually, but that’s harmless)
					$this->showSuccessMessage( $sourceLexemeId, $targetLexemeId );
				} else {
					$this->mergeLexemes( $sourceLexemeId, $targetLexemeId );
				}
			} else {
				if ( !$sourceLexemeId ) {
					$this->showInvalidLexemeIdError( $sourceId );
				}
				if ( !$targetLexemeId ) {
					$this->showInvalidLexemeIdError( $targetId );
				}
			}
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
		AnonymousEditWarningBuilder $anonymousEditWarningBuilder,
		EntityTitleLookup $entityTitleLookup,
		ExceptionLocalizer $exceptionLocalizer,
		SettingsArray $repoSettings,
		TokenCheckInteractor $tokenCheckInteractor,
		MergeLexemesInteractor $mergeLexemesInteractor
	): self {
		return new self(
			$repoSettings->getSetting( 'specialPageTags' ),
			$mergeLexemesInteractor,
			$tokenCheckInteractor,
			$entityTitleLookup,
			$exceptionLocalizer,
			$permissionManager,
			$anonymousEditWarningBuilder
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
			return Html::rawElement(
				'p',
				[ 'class' => 'warning' ],
				$this->anonymousEditWarningBuilder->buildAnonymousEditWarningHTML( $fullTitle->getPrefixedText() )
			);
		}

		return '';
	}

	private function mergeLexemes( LexemeId $sourceId, LexemeId $targetId ): void {
		try {
			$this->tokenCheckInteractor->checkRequestToken( $this->getContext(), 'wpEditToken' );
		} catch ( TokenCheckException $e ) {
			$message = $this->exceptionLocalizer->getExceptionMessage( $e );
			$this->showErrorHTML( $message->parse() );
			return;
		}

		try {
			$status = $this->mergeInteractor->mergeLexemes(
				$sourceId,
				$targetId,
				$this->getContext(),
				null,
				false,
				$this->tags
			);
			$savedTempUser = $status->getSavedTempUser();
		} catch ( MergingException $e ) {
			$this->showErrorHTML( $e->getErrorMessage()->escaped() );
			return;
		}

		if ( $savedTempUser !== null ) {
			$redirectUrl = '';
			$this->getHookRunner()->onTempUserCreatedRedirect(
				$this->getRequest()->getSession(),
				$savedTempUser,
				$this->getPageTitle()->getPrefixedDBkey(),
				wfArrayToCgi( [
					self::FROM_ID => $sourceId->getSerialization(),
					self::TO_ID => $targetId->getSerialization(),
					self::SUCCESS => '1',
				] ),
				'',
				$redirectUrl
			);
			if ( $redirectUrl ) {
				$this->getOutput()->redirect( $redirectUrl );
				return; // success will be shown when returning here from redirect
			}
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

	private function showInvalidLexemeIdError( string $id ): void {
		$this->showErrorHTML(
			( new Message( 'wikibase-lexeme-mergelexemes-error-invalid-id', [ $id ] ) )
				->escaped()
		);
	}

	protected function getGroupName(): string {
		return 'wikibase';
	}

	protected function showErrorHTML( string $error ): void {
		$this->getOutput()->addHTML( '<p class="error">' . $error . '</p>' );
	}

	public function getDescription(): Message {
		return $this->msg( 'special-mergelexemes' );
	}

}
