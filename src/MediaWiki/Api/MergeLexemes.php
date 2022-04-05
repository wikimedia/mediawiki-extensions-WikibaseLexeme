<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use ApiBase;
use ApiMain;
use ApiUsageException;
use Exception;
use InvalidArgumentException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * WikibaseLexeme API endpoint wblmergelexemes
 *
 * @license GPL-2.0-or-later
 */
class MergeLexemes extends ApiBase {

	public const SOURCE_ID_PARAM = 'source';
	public const TARGET_ID_PARAM = 'target';
	public const SUMMARY_PARAM = 'summary';
	private const BOT_PARAM = 'bot';

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		callable $errorReporterCallback
	) {
		parent::__construct( $mainModule, $moduleName );
		$this->errorReporter = $errorReporterCallback( $this );
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ApiHelperFactory $apiHelperFactory
	): self {
		return new self(
			$mainModule,
			$moduleName,
			static function ( $module ) use ( $apiHelperFactory ) {
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
		$services = WikibaseLexemeServices::newInstance();

		$sourceId = $this->getLexemeIdFromParamOrDie( $params[self::SOURCE_ID_PARAM] );
		$targetId = $this->getLexemeIdFromParamOrDie( $params[self::TARGET_ID_PARAM] );

		try {
			$services->newMergeLexemesInteractor()->mergeLexemes(
				$sourceId,
				$targetId,
				$this->getContext(),
				$params[self::SUMMARY_PARAM],
				$params[self::BOT_PARAM],
				$params['tags'] ?: []
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

	private function getLexemeIdFromParamOrDie( $serialization ): LexemeId {
		try {
			return new LexemeId( $serialization );
		} catch ( InvalidArgumentException $e ) {
			$this->errorReporter->dieException( $e, 'invalid-entity-id' );
		}
	}

	/** @inheritDoc */
	protected function getAllowedParams() {
		return [
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
			]
		];
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

	/**
	 * @return bool
	 */
	private function showSuccessMessage() {
		return $this->getResult()->addContentValue( null, 'success', 1 );
	}

}
