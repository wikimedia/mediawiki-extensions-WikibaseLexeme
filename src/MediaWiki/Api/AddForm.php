<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Api;

use LogicException;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiCreateTempUserTrait;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use MediaWiki\Message\Message;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\Lexeme\Domain\Model\Exceptions\ConflictException;
use Wikibase\Lexeme\Domain\Model\Form;
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
use Wikibase\Repo\Api\ResultBuilder;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\EditEntity\EditEntityStatus;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\SummaryFormatter;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddForm extends ApiBase {

	use ApiCreateTempUserTrait;

	private const LATEST_REVISION = 0;

	private AddFormRequestParser $requestParser;
	private ResultBuilder $resultBuilder;
	private ApiErrorReporter $errorReporter;
	private FormSerializer $formSerializer;
	private MediaWikiEditEntityFactory $editEntityFactory;
	private SummaryFormatter $summaryFormatter;
	private EntityRevisionLookup $entityRevisionLookup;

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ApiHelperFactory $apiHelperFactory,
		SerializerFactory $baseDataModelSerializerFactory,
		MediaWikiEditEntityFactory $editEntityFactory,
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
			$apiHelperFactory
		);
	}

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		AddFormRequestParser $requestParser,
		FormSerializer $formSerializer,
		EntityRevisionLookup $entityRevisionLookup,
		MediaWikiEditEntityFactory $editEntityFactory,
		SummaryFormatter $summaryFormatter,
		ApiHelperFactory $apiHelperFactory
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->resultBuilder = $apiHelperFactory->getResultBuilder( $this );
		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->requestParser = $requestParser;
		$this->formSerializer = $formSerializer;
		$this->editEntityFactory = $editEntityFactory;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->summaryFormatter = $summaryFormatter;
	}

	/**
	 * @see ApiBase::execute()
	 *
	 * @throws ApiUsageException
	 */
	public function execute(): void {
		/*
		 * {
			  "representations": [
				"en-gb": {
				  "value": "colour",
				  "language": "en-gb"
				},
				"en-us": {
				  "value": "color",
				  "language": "en-us"
				}
			  ],
			  "grammaticalFeatures": [
				"Q1",
				"Q2"
			  ]
			}
		 *
		 */

		//FIXME: Representation text normalization

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
			$this->errorReporter->dieException( $exception, 'unprocessable-request' );
		}

		$baseRevId = $request->getBaseRevId() ?: $lexemeRevision->getRevisionId();

		$flags = $this->buildSaveFlags( $params );
		$status = $this->saveNewLexemeRevision( $lexeme, $baseRevId, $summary, $flags, $params['tags'] ?: [] );

		if ( !$status->isGood() ) {
			$this->dieStatus( $status );
		}

		$this->fillApiResultFromStatus( $status, $params );
	}

	protected function getAllowedParams(): array {
		return array_merge( [
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
			],
		], $this->getCreateTempUserParams() );
	}

	public function isWriteMode(): bool {
		return true;
	}

	/**
	 * As long as this codebase is in development and APIs might change any time without notice, we
	 * mark all as internal. This adds an "unstable" notice, but does not hide them in any way.
	 */
	public function isInternal(): bool {
		return true;
	}

	public function needsToken(): string {
		return 'csrf';
	}

	public function mustBePosted(): bool {
		return true;
	}

	protected function getExamplesMessages(): array {
		$lexemeId = 'L12';
		$exampleData = [
			'representations' => [
				'en-us' => [ 'value' => 'color', 'language' => 'en-us' ],
				'en-gb' => [ 'value' => 'colour', 'language' => 'en-gb' ],
			],
			'grammaticalFeatures' => [
				'Q1', 'Q2',
			],
		];

		$query = http_build_query( [
			'action' => $this->getModuleName(),
			AddFormRequestParser::PARAM_LEXEME_ID => $lexemeId,
			AddFormRequestParser::PARAM_DATA => json_encode( $exampleData ),
		] );

		$languages = array_column( $exampleData['representations'], 'language' );
		$representations = array_column( $exampleData['representations'], 'value' );

		$representationsText = $this->getLanguage()->commaList( $representations );
		$languagesText = $this->getLanguage()->commaList( $languages );
		$grammaticalFeaturesText = $this->getLanguage()->commaList( $exampleData['grammaticalFeatures'] );

		$exampleMessage = new Message(
			'apihelp-wbladdform-example-1',
			[
				$lexemeId,
				$representationsText,
				$languagesText,
				$grammaticalFeaturesText,
			]
		);

		return [
			urldecode( $query ) => $exampleMessage,
		];
	}

	private function getFormWithMaxId( Lexeme $lexeme ): Form {
		// TODO: This is all rather nasty
		$maxIdNumber = $lexeme->getForms()->maxFormIdNumber();
		// TODO: Use some service to get the ID object!
		$formId = new FormId( $lexeme->getId() . '-F' . $maxIdNumber );
		return $lexeme->getForm( $formId );
	}

	/**
	 * @throws ApiUsageException
	 */
	private function getBaseLexemeRevisionFromRequest( AddFormRequest $request ): EntityRevision {
		$lexemeId = $request->getLexemeId();
		try {
			$lexemeRevision = $this->entityRevisionLookup->getEntityRevision(
				$lexemeId,
				$request->getBaseRevId() ?? self::LATEST_REVISION,
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

	private function buildSaveFlags( array $params ): int {
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
		int $baseRevId,
		FormatableSummary $summary,
		int $flags,
		array $tags
	): EditEntityStatus {
		$editEntity = $this->editEntityFactory->newEditEntity(
			$this->getContext(),
			$lexeme->getId(),
			$baseRevId
		);
		$summaryString = $this->summaryFormatter->formatSummary(
			$summary
		);

		$tokenThatDoesNotNeedChecking = false;
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

	private function fillApiResultFromStatus( EditEntityStatus $status, array $params ): void {
		$entityRevision = $status->getRevision();

		/** @var Lexeme $editedLexeme */
		$editedLexeme = $entityRevision->getEntity();
		'@phan-var Lexeme $editedLexeme';
		$newForm = $this->getFormWithMaxId( $editedLexeme );
		$serializedForm = $this->formSerializer->serialize( $newForm );

		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, null );
		$this->resultBuilder->markSuccess();
		$this->getResult()->addValue( null, 'form', $serializedForm );
		$this->resultBuilder->addTempUser( $status, fn ( $user ) => $this->getTempUserRedirectUrl( $params, $user ) );
	}

}
