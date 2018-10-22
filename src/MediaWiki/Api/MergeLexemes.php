<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use ApiBase;
use ApiMain;
use ApiUsageException;
use Exception;
use InvalidArgumentException;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Lexeme\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\WikibaseRepo;

/**
 * WikibaseLexeme API endpoint wblmergelexemes
 *
 * @license GPL-2.0-or-later
 */
class MergeLexemes extends ApiBase {

	const SOURCE_ID_PARAM = 'source';
	const TARGET_ID_PARAM = 'target';
	const SUMMARY_PARAM = 'summary';
	const BOT_PARAM = 'bot';

	/**
	 * @var \Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor
	 */
	private $mergeInteractor;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		MergeLexemesInteractor $mergeInteractor,
		callable $errorReporterCallback
	) {
		parent::__construct( $mainModule, $moduleName );
		$this->mergeInteractor = $mergeInteractor;
		$this->errorReporter = $errorReporterCallback( $this );
	}

	public static function newFromGlobalState( ApiMain $mainModule, $moduleName ) {
		$repo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $repo->getApiHelperFactory( $mainModule->getContext() );
		return new self(
			$mainModule,
			$moduleName,
			WikibaseLexemeServices::getLexemeMergeInteractor(),
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getErrorReporter( $module );
			}
		);
	}

	/**
	 * @see ApiBase::execute()
	 *
	 * @throws ApiUsageException
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$source = $this->getLexemeIdFromParam( $params[self::SOURCE_ID_PARAM] );
		$target = $this->getLexemeIdFromParam( $params[self::TARGET_ID_PARAM] );

		try {
			$this->mergeInteractor->mergeLexemes(
				$source,
				$target,
				$params[self::SUMMARY_PARAM],
				$params[self::BOT_PARAM]
			);
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

		$this->showSuccessMessage();
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return [
			self::SOURCE_ID_PARAM => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			],
			self::TARGET_ID_PARAM => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			],
			self::SUMMARY_PARAM => [
				self::PARAM_TYPE => 'string',
			],
			self::BOT_PARAM => [
				self::PARAM_TYPE => 'boolean',
				self::PARAM_DFLT => false,
			],
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return [
			'action=wblmergelexemes&source=L123&target=L321' =>
				'apihelp-wblmergelexemes-example-1',
		];
	}

	/**
	 * @see ApiBase::needsToken()
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @see ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return true;
	}

	private function getLexemeIdFromParam( $serialization ): LexemeId {
		try {
			return new LexemeId( $serialization );
		} catch ( InvalidArgumentException $e ) {
			$this->errorReporter->dieException( $e, 'invalid-entity-id' );
		}
	}

	/**
	 * @return bool
	 */
	private function showSuccessMessage() {
		return $this->getResult()->addContentValue( null, 'success', 1 );
	}

}
