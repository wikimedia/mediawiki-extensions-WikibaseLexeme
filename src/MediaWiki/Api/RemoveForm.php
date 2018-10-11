<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use ApiBase;
use ApiMain;
use LogicException;
use Message;
use Wikibase\EditEntityFactory;
use Wikibase\Lexeme\MediaWiki\Api\Error\FormNotFound;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeNotFound;
use Wikibase\Lexeme\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lexeme\Domain\DataModel\Lexeme;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class RemoveForm extends ApiBase {

	const LATEST_REVISION = 0;

	/**
	 * @var RemoveFormRequestParser
	 */
	private $requestParser;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var EditEntityFactory
	 */
	private $editEntityFactory;

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @return self
	 */
	public static function newFromGlobalState( ApiMain $mainModule, $moduleName ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

		return new self(
			$mainModule,
			$moduleName,
			new RemoveFormRequestParser(
				new FormIdDeserializer(
					$wikibaseRepo->getEntityIdParser()
				)
			),
			$wikibaseRepo->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			$wikibaseRepo->newEditEntityFactory( $mainModule->getContext() ),
			$wikibaseRepo->getSummaryFormatter(),
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getErrorReporter( $module );
			}
		);
	}

	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		RemoveFormRequestParser $requestParser,
		EntityRevisionLookup $entityRevisionLookup,
		EditEntityFactory $editEntityFactory,
		SummaryFormatter $summaryFormatter,
		callable $errorReporterInstantiator
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->errorReporter = $errorReporterInstantiator( $this );
		$this->requestParser = $requestParser;
		$this->editEntityFactory = $editEntityFactory;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->summaryFormatter = $summaryFormatter;
	}

	/**
	 * @see ApiBase::execute()
	 *
	 * @throws \ApiUsageException
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$request = $this->requestParser->parse( $params );

		try {
			$formId = $request->getFormId();
			$lexemeId = $formId->getLexemeId();

			$lexemeRevision = $this->entityRevisionLookup->getEntityRevision(
				$lexemeId,
				self::LATEST_REVISION,
				EntityRevisionLookup::LATEST_FROM_MASTER
			);

			if ( !$lexemeRevision ) {
				$error = new LexemeNotFound( $lexemeId );
				$this->dieWithError( $error->asApiMessage( RemoveFormRequestParser::PARAM_FORM_ID, [] ) );
			}

			/** @var Lexeme $lexeme */
			$lexeme = $lexemeRevision->getEntity();

			if ( $lexeme->getForms()->getById( $formId ) === null ) {
				$error = new FormNotFound( $formId );
				$this->dieWithError( $error->asApiMessage( RemoveFormRequestParser::PARAM_FORM_ID, [] ) );
			}
		} catch ( StorageException $e ) {
			//TODO Test it
			if ( $e->getStatus() ) {
				$this->dieStatus( $e->getStatus() );
			} else {
				throw new LogicException(
					'StorageException caught with no status',
					0,
					$e
				);
			}
		}

		$summary = new Summary();
		$changeOp = $request->getChangeOp();

		$result = $changeOp->validate( $lexeme );
		if ( !$result->isValid() ) {
			$this->errorReporter->dieException(
				new ChangeOpValidationException( $result ),
				'modification-failed'
			);
		}

		$changeOp->apply( $lexeme, $summary );

		$editEntity = $this->editEntityFactory->newEditEntity(
			$this->getUser(),
			$lexemeId,
			$lexemeRevision->getRevisionId()
		);
		$flags = EDIT_UPDATE;
		if ( isset( $params['bot'] ) && $params['bot'] && $this->getUser()->isAllowed( 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		$tokenThatDoesNotNeedChecking = false;
		//FIXME: Handle failure
		$status = $editEntity->attemptSave(
			$lexeme,
			$this->summaryFormatter->formatSummary( $summary ),
			$flags,
			$tokenThatDoesNotNeedChecking
		);

		if ( !$status->isGood() ) {
			$this->dieStatus( $status );
		}

		/** @var EntityRevision $entityRevision */
		$entityRevision = $status->getValue()['revision'];

		$apiResult = $this->getResult();
		$apiResult->addValue( null, 'lastrevid', $entityRevision->getRevisionId() );
		$apiResult->addValue( null, 'success', 1 );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		// TODO baserevid (not in addform etc currently....)
		return array_merge(
			[
				RemoveFormRequestParser::PARAM_FORM_ID => [
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				],
				'bot' => [
					self::PARAM_TYPE => 'boolean',
					self::PARAM_DFLT => false,
				]
			]
		);
	}

	/**
	 * @see ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * As long as this codebase is in development and APIs might change any time without notice, we
	 * mark all as internal. This adds an "unstable" notice, but does not hide them in any way.
	 */
	public function isInternal() {
		return true;
	}

	/**
	 * @see ApiBase::needsToken()
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @see ApiBase::mustBePosted()
	 */
	public function mustBePosted() {
		return true;
	}

	protected function getExamplesMessages() {
		$formId = 'L10-F20';

		$query = http_build_query( [
			'action' => $this->getModuleName(),
			RemoveFormRequestParser::PARAM_FORM_ID => $formId
		] );

		$exampleMessage = new Message(
			'apihelp-wblremoveform-example-1',
			[ $formId ]
		);

		return [
			urldecode( $query ) => $exampleMessage
		];
	}

}
