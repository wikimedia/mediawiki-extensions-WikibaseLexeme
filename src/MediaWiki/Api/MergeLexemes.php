<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use ApiBase;
use ApiCreateTempUserTrait;
use ApiMain;
use ApiUsageException;
use Exception;
use InvalidArgumentException;
use MediaWiki\User\UserIdentity;
use Wikibase\Lexeme\Domain\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * WikibaseLexeme API endpoint wblmergelexemes
 *
 * @license GPL-2.0-or-later
 */
class MergeLexemes extends ApiBase {

	use ApiCreateTempUserTrait;

	public const SOURCE_ID_PARAM = 'source';
	public const TARGET_ID_PARAM = 'target';
	public const SUMMARY_PARAM = 'summary';
	private const BOT_PARAM = 'bot';

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	private MergeLexemesInteractor $mergeLexemesInteractor;

	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		callable $errorReporterCallback,
		MergeLexemesInteractor $mergeLexemesInteractor
	) {
		parent::__construct( $mainModule, $moduleName );
		$this->errorReporter = $errorReporterCallback( $this );
		$this->mergeLexemesInteractor = $mergeLexemesInteractor;
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ApiHelperFactory $apiHelperFactory,
		MergeLexemesInteractor $mergeLexemesInteractor
	): self {
		return new self(
			$mainModule,
			$moduleName,
			static function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getErrorReporter( $module );
			},
			$mergeLexemesInteractor
		);
	}

	/**
	 * @see ApiBase::execute()
	 *
	 * @throws ApiUsageException
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$sourceId = $this->getLexemeIdFromParamOrDie( $params[self::SOURCE_ID_PARAM] );
		$targetId = $this->getLexemeIdFromParamOrDie( $params[self::TARGET_ID_PARAM] );

		try {
			$status = $this->mergeLexemesInteractor->mergeLexemes(
				$sourceId,
				$targetId,
				$this->getContext(),
				$params[self::SUMMARY_PARAM],
				$params[self::BOT_PARAM],
				$params['tags'] ?: []
			);
			$savedTempUser = $status->getSavedTempUser();
		} catch ( MergingException $e ) {
			$this->errorReporter->dieException(
				$e,
				$e->getApiErrorCode()
			);
		} catch ( Exception $e ) {
			$this->errorReporter->dieException(
				$e,
				'bad-request'
			);
		}

		$this->showSuccessMessage( $params, $savedTempUser );
	}

	private function getLexemeIdFromParamOrDie( $serialization ): LexemeId {
		try {
			return new LexemeId( $serialization );
		} catch ( InvalidArgumentException $e ) {
			$this->errorReporter->dieException( $e, 'invalid-entity-id' );
		}
	}

	/** @inheritDoc */
	protected function getAllowedParams() {
		return array_merge( [
			self::SOURCE_ID_PARAM => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			self::TARGET_ID_PARAM => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			self::SUMMARY_PARAM => [
				ParamValidator::PARAM_TYPE => 'string',
			],
			'tags' => [
				ParamValidator::PARAM_TYPE => 'tags',
				ParamValidator::PARAM_ISMULTI => true,
			],
			self::BOT_PARAM => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_DEFAULT => false,
			],
		], $this->getCreateTempUserParams() );
	}

	/** @inheritDoc */
	protected function getExamplesMessages() {
		return [
			'action=wblmergelexemes&source=L123&target=L321' =>
				'apihelp-wblmergelexemes-example-1',
		];
	}

	/** @inheritDoc */
	public function needsToken() {
		return 'csrf';
	}

	/** @inheritDoc */
	public function isWriteMode() {
		return true;
	}

	private function showSuccessMessage( array $params, ?UserIdentity $savedTempUser ) {
		$result = $this->getResult();
		$result->addContentValue( null, 'success', 1 );

		if ( $savedTempUser !== null ) {
			$result->addValue( null, 'tempusercreated', $savedTempUser->getName() );
			$redirectUrl = $this->getTempUserRedirectUrl( $params, $savedTempUser );
			if ( $redirectUrl === '' ) {
				$redirectUrl = null;
			}
			$result->addValue( null, 'tempuserredirect', $redirectUrl );
		}
	}

}
