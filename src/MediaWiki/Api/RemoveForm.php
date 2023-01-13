<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use ApiBase;
use ApiMain;
use LogicException;
use Message;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\MediaWiki\Api\Error\FormNotFound;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeNotFound;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\SummaryFormatter;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class RemoveForm extends ApiBase {

	private const LATEST_REVISION = 0;

	/**
	 * @var RemoveFormRequestParser
	 */
	private $requestParser;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var MediawikiEditEntityFactory
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

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ApiHelperFactory $apiHelperFactory,
		MediawikiEditEntityFactory $editEntityFactory,
		EntityIdParser $entityIdParser,
		Store $store,
		SummaryFormatter $summaryFormatter
	): self {
		return new self(
			$mainModule,
			$moduleName,
			new RemoveFormRequestParser(
				new FormIdDeserializer( $entityIdParser )
			),
			$store->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			$editEntityFactory,
			$summaryFormatter,
			static function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getErrorReporter( $module );
			}
		);
	}

	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		RemoveFormRequestParser $requestParser,
		EntityRevisionLookup $entityRevisionLookup,
		MediawikiEditEntityFactory $editEntityFactory,
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
		if ( $request->getBaseRevId() ) {
			$baseRevId = $request->getBaseRevId();
		} else {
			$baseRevId = self::LATEST_REVISION;
		}

		try {
			$formId = $request->getFormId();
			$lexemeId = $formId->getLexemeId();

			$lexemeRevision = $this->entityRevisionLookup->getEntityRevision(
				$lexemeId,
				$baseRevId,
				LookupConstants::LATEST_FROM_MASTER
			);

			if ( !$lexemeRevision ) {
				$error = new LexemeNotFound( $lexemeId );
				$this->dieWithError( $error->asApiMessage( RemoveFormRequestParser::PARAM_FORM_ID, [] ) );
			}

			$baseRevId = $lexemeRevision->getRevisionId();
			/** @var Lexeme $lexeme */
			$lexeme = $lexemeRevision->getEntity();
			'@phan-var Lexeme $lexeme';

			if ( $lexeme->getForms()->getById( $formId ) === null ) {
				$error = new FormNotFound( $formId );
				$this->dieWithError( $error->asApiMessage( RemoveFormRequestParser::PARAM_FORM_ID, [] ) );
			}
		} catch ( StorageException $e ) {
			// TODO Test it
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
			$this->getContext(),
			$lexemeId,
			$baseRevId
		);
		$flags = EDIT_UPDATE;
		if ( isset( $params['bot'] ) && $params['bot'] &&
			$this->getPermissionManager()->userHasRight( $this->getUser(), 'bot' )
		) {
			$flags |= EDIT_FORCE_BOT;
		}

		$tokenThatDoesNotNeedChecking = false;
		// FIXME: Handle failure
		$status = $editEntity->attemptSave(
			$lexeme,
			$this->summaryFormatter->formatSummary( $summary ),
			$flags,
			$tokenThatDoesNotNeedChecking,
			null,
			$params['tags'] ?: []
		);

		if ( !$status->isOK() ) {
			$this->dieStatus( $status );
		}

		/** @var EntityRevision $entityRevision */
		$entityRevision = $status->getValue()['revision'];

		$apiResult = $this->getResult();
		$apiResult->addValue( null, 'lastrevid', $entityRevision->getRevisionId() );
		$apiResult->addValue( null, 'success', 1 );
	}

	/** @inheritDoc */
	protected function getAllowedParams() {
		return array_merge(
			[
				RemoveFormRequestParser::PARAM_FORM_ID => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => true,
				],
				'tags' => [
					ParamValidator::PARAM_TYPE => 'tags',
					ParamValidator::PARAM_ISMULTI => true,
				],
				'bot' => [
					ParamValidator::PARAM_TYPE => 'boolean',
					ParamValidator::PARAM_DEFAULT => false,
				],
				RemoveFormRequestParser::PARAM_BASEREVID => [
					ParamValidator::PARAM_TYPE => 'integer',
				],
			]
		);
	}

	/** @inheritDoc */
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

	/** @inheritDoc */
	public function needsToken() {
		return 'csrf';
	}

	/** @inheritDoc */
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
