<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Api;

use ApiCreateTempUserTrait;
use ApiMain;
use Deserializers\Deserializer;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\MediaWiki\Api\Error\SenseNotFound;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\Serialization\SenseSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\Api\ResultBuilder;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\EditEntity\EditEntityStatus;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\SummaryFormatter;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class EditSenseElements extends \ApiBase {

	use ApiCreateTempUserTrait;

	private const LATEST_REVISION = 0;

	private EntityRevisionLookup $entityRevisionLookup;
	private MediaWikiEditEntityFactory $editEntityFactory;
	private EditSenseElementsRequestParser $requestParser;
	private SummaryFormatter $summaryFormatter;
	private SenseSerializer $senseSerializer;
	private ResultBuilder $resultBuilder;
	private ApiErrorReporter $errorReporter;
	private EntityStore $entityStore;

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ApiHelperFactory $apiHelperFactory,
		SerializerFactory $baseDataModelSerializerFactory,
		ChangeOpFactoryProvider $changeOpFactoryProvider,
		MediaWikiEditEntityFactory $editEntityFactory,
		EntityIdParser $entityIdParser,
		EntityStore $entityStore,
		Deserializer $externalFormatStatementDeserializer,
		Store $store,
		StringNormalizer $stringNormalizer,
		SummaryFormatter $summaryFormatter
	): self {
		$senseSerializer = new SenseSerializer(
			$baseDataModelSerializerFactory->newTermListSerializer(),
			$baseDataModelSerializerFactory->newStatementListSerializer()
		);

		return new self(
			$mainModule,
			$moduleName,
			$store->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			$editEntityFactory,
			new EditSenseElementsRequestParser(
				new SenseIdDeserializer( $entityIdParser ),
				new EditSenseChangeOpDeserializer(
					new GlossesChangeOpDeserializer(
						new TermDeserializer(),
						$stringNormalizer,
						new LexemeTermSerializationValidator(
							new LexemeTermLanguageValidator( WikibaseLexemeServices::getTermLanguages() )
						)
					),
					new ClaimsChangeOpDeserializer(
						$externalFormatStatementDeserializer,
						$changeOpFactoryProvider->getStatementChangeOpFactory()
					)
				)
			),
			$summaryFormatter,
			$senseSerializer,
			$apiHelperFactory,
			$entityStore
		);
	}

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		EntityRevisionLookup $entityRevisionLookup,
		MediaWikiEditEntityFactory $editEntityFactory,
		EditSenseElementsRequestParser $requestParser,
		SummaryFormatter $summaryFormatter,
		SenseSerializer $senseSerializer,
		ApiHelperFactory $apiHelperFactory,
		EntityStore $entityStore
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->editEntityFactory = $editEntityFactory;
		$this->requestParser = $requestParser;
		$this->summaryFormatter = $summaryFormatter;
		$this->senseSerializer = $senseSerializer;
		$this->resultBuilder = $apiHelperFactory->getResultBuilder( $this );
		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->entityStore = $entityStore;
	}

	/**
	 * @inheritDoc
	 * @suppress PhanTypeMismatchArgument
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();
		$request = $this->requestParser->parse( $params );
		if ( $request->getBaseRevId() ) {
			$baseRevId = $request->getBaseRevId();
		} else {
			$baseRevId = self::LATEST_REVISION;
		}

		$senseId = $request->getSenseId();
		$senseRevision = $this->entityRevisionLookup->getEntityRevision(
			$senseId,
			self::LATEST_REVISION,
			LookupConstants::LATEST_FROM_MASTER
		);

		if ( $senseRevision === null ) {
			$error = new SenseNotFound( $senseId );
			$this->dieWithError(
				$error->asApiMessage( EditSenseElementsRequestParser::PARAM_SENSE_ID, [] )
			);
		}
		$sense = $senseRevision->getEntity();
		$baseRevId = $this->getRevIdForWhenUserWasLastToEdit(
			$senseRevision->getRevisionId(),
			$baseRevId,
			$senseId->getLexemeId()
		);
		$changeOp = $request->getChangeOp();

		$result = $changeOp->validate( $sense );
		if ( !$result->isValid() ) {
			$this->errorReporter->dieException(
				new ChangeOpValidationException( $result ),
				'modification-failed'
			);
		}

		$summary = new Summary();
		try {
			$changeOp->apply( $sense, $summary );
		} catch ( ChangeOpException $exception ) {
			$this->errorReporter->dieException( $exception,  'unprocessable-request' );
		}

		$summaryString = $this->summaryFormatter->formatSummary( $summary );

		$status = $this->saveSense( $sense, $summaryString, $baseRevId, $params );

		if ( !$status->isOK() ) {
			$this->dieStatus( $status );
		}

		$this->generateResponse( $sense, $status, $params );
	}

	private function saveSense(
		Sense $sense,
		string $summary,
		int $baseRevisionId,
		array $params
	): EditEntityStatus {
		$editEntity = $this->editEntityFactory->newEditEntity(
			$this->getContext(),
			$sense->getId(),
			$baseRevisionId
		);

		// TODO: bot flag should probably be part of the request
		$flags = EDIT_UPDATE;
		if ( isset( $params['bot'] ) && $params['bot'] &&
			$this->getPermissionManager()->userHasRight( $this->getUser(), 'bot' )
		) {
			$flags |= EDIT_FORCE_BOT;
		}

		$tokenThatDoesNotNeedChecking = false;
		return $editEntity->attemptSave(
			$sense,
			$summary,
			$flags,
			$tokenThatDoesNotNeedChecking,
			null,
			$params['tags'] ?: []
		);
	}

	private function generateResponse( Sense $sense, EditEntityStatus $status, array $params ): void {
		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, null );
		$this->resultBuilder->markSuccess();

		$serializedSense = $this->senseSerializer->serialize( $sense );
		unset( $serializedSense['claims'] );
		$this->getResult()->addValue( null, 'sense', $serializedSense );

		$this->resultBuilder->addTempUser( $status, fn ( $user ) => $this->getTempUserRedirectUrl( $params, $user ) );
	}

	protected function getAllowedParams(): array {
		return array_merge( [
			EditSenseElementsRequestParser::PARAM_SENSE_ID => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			EditSenseElementsRequestParser::PARAM_DATA => [
				ParamValidator::PARAM_TYPE => 'text',
				ParamValidator::PARAM_REQUIRED => true,
			],
			EditSenseElementsRequestParser::PARAM_BASEREVID => [
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
		$senseId = 'L12-S1';
		$exampleData = [
			'glosses' => [
				'en' => [
					'value' => 'the property of an object of producing different sensations on the eye',
					'language' => 'en',
				],
				'de' => [
					'value' => 'Eigenschaft eines Objekts, verschiedene Sinneseindrücke im Auge zu verursachen',
					'language' => 'de',
				],
			],
		];

		$query = http_build_query( [
			'action' => $this->getModuleName(),
			EditSenseElementsRequestParser::PARAM_SENSE_ID => $senseId,
			EditSenseElementsRequestParser::PARAM_DATA => json_encode( $exampleData ),
		] );

		$languages = array_column( $exampleData['glosses'], 'language' );
		$glosses = array_column( $exampleData['glosses'], 'value' );

		$glossesText = $this->getLanguage()->commaList( $glosses );
		$languagesText = $this->getLanguage()->commaList( $languages );

		$exampleMessage = new \Message(
			'apihelp-wbleditsenseelements-example-1',
			[
				$senseId,
				$glossesText,
				$languagesText,
			]
		);

		return [
			urldecode( $query ) => $exampleMessage,
		];
	}

	/**
	 * Returns $latestRevisionId if all of edits since $baseRevId are done
	 * by the same user, otherwise returns $baseRevId.
	 */
	private function getRevIdForWhenUserWasLastToEdit(
		int $latestRevisionId,
		int $baseRevId,
		EntityId $entityId
	): int {
		if ( $baseRevId === self::LATEST_REVISION || $latestRevisionId === $baseRevId ) {
			return $latestRevisionId;
		}

		$userWasLastToEdit = $this->entityStore->userWasLastToEdit(
			$this->getUser(),
			$entityId,
			$baseRevId
		);
		if ( $userWasLastToEdit ) {
			return $latestRevisionId;
		}

		return $baseRevId;
	}

}
