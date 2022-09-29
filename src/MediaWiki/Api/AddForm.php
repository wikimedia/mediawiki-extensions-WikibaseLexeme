<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use ApiBase;
use ApiMain;
use LogicException;
use RuntimeException;
use Status;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\Lexeme\Domain\Model\Exceptions\ConflictException;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeNotFound;
use Wikibase\Lexeme\Serialization\FormSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\SummaryFormatter;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddForm extends ApiBase {

	private const LATEST_REVISION = 0;

	/**
	 * @var AddFormRequestParser
	 */
	private $requestParser;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var FormSerializer
	 */
	private $formSerializer;

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
		SerializerFactory $baseDataModelSerializerFactory,
		MediawikiEditEntityFactory $editEntityFactory,
		EntityIdParser $entityIdParser,
		Store $store,
		SummaryFormatter $summaryFormatter
	): self {
		$formSerializer = new FormSerializer(
			$baseDataModelSerializerFactory->newTermListSerializer(),
			$baseDataModelSerializerFactory->newStatementListSerializer()
		);

		return new self(
			$mainModule,
			$moduleName,
			new AddFormRequestParser(
				$entityIdParser,
				WikibaseLexemeServices::getEditFormChangeOpDeserializer()
			),
			$formSerializer,
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
		AddFormRequestParser $requestParser,
		FormSerializer $formSerializer,
		EntityRevisionLookup $entityRevisionLookup,
		MediawikiEditEntityFactory $editEntityFactory,
		SummaryFormatter $summaryFormatter,
		callable $errorReporterInstantiator
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->errorReporter = $errorReporterInstantiator( $this );
		$this->requestParser = $requestParser;
		$this->formSerializer = $formSerializer;
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
		/*
		 * {
			  "representations": [
				"en-GB": {
				  "value": "colour",
				  "language": "en-GB"
				},
				"en-US": {
				  "value": "color",
				  "language": "en-US"
				}
			  ],
			  "grammaticalFeatures": [
				"Q1",
				"Q2"
			  ]
			}
		 *
		 */

		//FIXME: Response structure? - Added form
		//FIXME: Representation text normalization

		//TODO: Corresponding HTTP codes on failure (e.g. 400, 404, 422) (?)
		//TODO: Documenting response structure. Is it possible?

		$params = $this->extractRequestParams();
		$request = $this->requestParser->parse( $params );

		$lexemeRevision = $this->getBaseLexemeRevisionFromRequest( $request );
		/** @var Lexeme $lexeme */
		$lexeme = $lexemeRevision->getEntity();
		$changeOp = $request->getChangeOp();

		$summary = new Summary();
		try {
			$changeOp->apply( $lexeme, $summary );
		} catch ( ChangeOpException $exception ) {
			$this->errorReporter->dieException( $exception,  'unprocessable-request' );
		}

		if ( $request->getBaseRevId() ) {
			$baseRevId = $request->getBaseRevId();
		} else {
			$baseRevId = $lexemeRevision->getRevisionId();
		}

		$flags = $this->buildSaveFlags( $params );
		$status = $this->saveNewLexemeRevision( $lexeme, $baseRevId, $summary, $flags, $params['tags'] ?: [] );

		if ( !$status->isGood() ) {
			$this->dieStatus( $status ); // Seems like it is good enough
		}

		$this->fillApiResultFromStatus( $status );
	}

	/** @inheritDoc */
	protected function getAllowedParams() {
		return array_merge(
			[
				AddFormRequestParser::PARAM_LEXEME_ID => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => true,
				],
				AddFormRequestParser::PARAM_DATA => [
					ParamValidator::PARAM_TYPE => 'text',
					ParamValidator::PARAM_REQUIRED => true,
				],
				AddFormRequestParser::PARAM_BASEREVID => [
					ParamValidator::PARAM_TYPE => 'integer',
				],
				'tags' => [
					ParamValidator::PARAM_TYPE => 'tags',
					ParamValidator::PARAM_ISMULTI => true,
				],
				'bot' => [
					ParamValidator::PARAM_TYPE => 'boolean',
					ParamValidator::PARAM_DEFAULT => false,
				]
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
		$lexemeId = 'L12';
		$exampleData = [
			'representations' => [
				'en-US' => [ 'value' => 'color', 'language' => 'en-US' ],
				'en-GB' => [ 'value' => 'colour', 'language' => 'en-GB' ],
			],
			'grammaticalFeatures' => [
				'Q1', 'Q2'
			]
		];

		$query = http_build_query( [
			'action' => $this->getModuleName(),
			AddFormRequestParser::PARAM_LEXEME_ID => $lexemeId,
			AddFormRequestParser::PARAM_DATA => json_encode( $exampleData )
		] );

		$languages = array_column( $exampleData['representations'], 'language' );
		$representations = array_column( $exampleData['representations'], 'value' );

		$representationsText = $this->getLanguage()->commaList( $representations );
		$languagesText = $this->getLanguage()->commaList( $languages );
		$grammaticalFeaturesText = $this->getLanguage()->commaList( $exampleData['grammaticalFeatures'] );

		$exampleMessage = new \Message(
			'apihelp-wbladdform-example-1',
			[
				$lexemeId,
				$representationsText,
				$languagesText,
				$grammaticalFeaturesText
			]
		);

		return [
			urldecode( $query ) => $exampleMessage
		];
	}

	private function getFormWithMaxId( Lexeme $lexeme ) {
		// TODO: This is all rather nasty
		$maxIdNumber = $lexeme->getForms()->maxFormIdNumber();
		// TODO: Use some service to get the ID object!
		$formId = new FormId( $lexeme->getId() . '-F' . $maxIdNumber );
		return $lexeme->getForm( $formId );
	}

	/**
	 * @throws \ApiUsageException
	 */
	private function getBaseLexemeRevisionFromRequest( AddFormRequest $request ): EntityRevision {
		$lexemeId = $request->getLexemeId();
		try {
			$lexemeRevision = $this->entityRevisionLookup->getEntityRevision(
				$lexemeId,
				self::LATEST_REVISION,
				LookupConstants::LATEST_FROM_MASTER
			);
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

		if ( !$lexemeRevision ) {
			$error = new LexemeNotFound( $lexemeId );
			$this->dieWithError( $error->asApiMessage( AddFormRequestParser::PARAM_LEXEME_ID, [] ) );
		}

		// @phan-suppress-next-line PhanTypeMismatchReturnNullable
		return $lexemeRevision;
	}

	/**
	 * @return int
	 */
	private function buildSaveFlags( array $params ) {
		$flags = EDIT_UPDATE;
		if ( isset( $params['bot'] ) && $params['bot'] &&
			$this->getPermissionManager()->userHasRight( $this->getUser(), 'bot' )
		) {
			$flags |= EDIT_FORCE_BOT;
		}
		return $flags;
	}

	private function saveNewLexemeRevision(
		EntityDocument $lexeme,
		?int $baseRevId,
		FormatableSummary $summary,
		$flags,
		array $tags
	): Status {
		$editEntity = $this->editEntityFactory->newEditEntity(
			$this->getContext(),
			$lexeme->getId(),
			$baseRevId
		);
		$summaryString = $this->summaryFormatter->formatSummary(
			$summary
		);

		$tokenThatDoesNotNeedChecking = false;
		// FIXME: Handle failure
		try {
			$status = $editEntity->attemptSave(
				$lexeme,
				$summaryString,
				$flags,
				$tokenThatDoesNotNeedChecking,
				null,
				$tags
			);
		} catch ( ConflictException $exception ) {
			$this->dieWithException( new RuntimeException( 'Edit conflict: ' . $exception->getMessage() ) );
		}

		return $status;
	}

	private function fillApiResultFromStatus( Status $status ) {

		/** @var EntityRevision $entityRevision */
		$entityRevision = $status->getValue()['revision'];
		$revisionId = $entityRevision->getRevisionId();

		/** @var Lexeme $editedLexeme */
		$editedLexeme = $entityRevision->getEntity();
		$newForm = $this->getFormWithMaxId( $editedLexeme );
		$serializedForm = $this->formSerializer->serialize( $newForm );

		$apiResult = $this->getResult();
		$apiResult->addValue( null, 'lastrevid', $revisionId );
		// TODO: Do we really need `success` property in response?
		$apiResult->addValue( null, 'success', 1 );
		$apiResult->addValue( null, 'form', $serializedForm );
	}

}
