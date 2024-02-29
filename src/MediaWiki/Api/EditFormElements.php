<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Api;

use ApiCreateTempUserTrait;
use ApiMain;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\MediaWiki\Api\Error\FormNotFound;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lexeme\Serialization\FormSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\Api\ResultBuilder;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\Repo\EditEntity\EditEntityStatus;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\SummaryFormatter;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class EditFormElements extends \ApiBase {

	use ApiCreateTempUserTrait;

	private const LATEST_REVISION = 0;

	private EntityRevisionLookup $entityRevisionLookup;
	private MediaWikiEditEntityFactory $editEntityFactory;
	private EditFormElementsRequestParser $requestParser;
	private SummaryFormatter $summaryFormatter;
	private FormSerializer $formSerializer;
	private ResultBuilder $resultBuilder;
	private ApiErrorReporter $errorReporter;
	private EntityStore $entityStore;

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ApiHelperFactory $apiHelperFactory,
		SerializerFactory $baseDataModelSerializerFactory,
		MediaWikiEditEntityFactory $editEntityFactory,
		EntityIdParser $entityIdParser,
		EntityStore $entityStore,
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
			$store->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			$editEntityFactory,
			new EditFormElementsRequestParser(
				new FormIdDeserializer( $entityIdParser ),
				WikibaseLexemeServices::getEditFormChangeOpDeserializer()
			),
			$summaryFormatter,
			$formSerializer,
			$apiHelperFactory,
			$entityStore
		);
	}

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		EntityRevisionLookup $entityRevisionLookup,
		MediaWikiEditEntityFactory $editEntityFactory,
		EditFormElementsRequestParser $requestParser,
		SummaryFormatter $summaryFormatter,
		FormSerializer $formSerializer,
		ApiHelperFactory $apiHelperFactory,
		EntityStore $entityStore
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->editEntityFactory = $editEntityFactory;
		$this->requestParser = $requestParser;
		$this->summaryFormatter = $summaryFormatter;
		$this->formSerializer = $formSerializer;
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

		$formId = $request->getFormId();

		$formRevision = $this->entityRevisionLookup->getEntityRevision(
			$formId,
			self::LATEST_REVISION,
			LookupConstants::LATEST_FROM_MASTER
		);

		if ( $formRevision === null ) {
			$error = new FormNotFound( $formId );
			$this->dieWithError( $error->asApiMessage( EditFormElementsRequestParser::PARAM_FORM_ID, [] ) );
		}

		$baseRevId = $this->getRevIdForWhenUserWasLastToEdit(
			$formRevision->getRevisionId(),
			$baseRevId,
			$formId->getLexemeId()
		);

		$form = $formRevision->getEntity();

		$changeOp = $request->getChangeOp();

		$result = $changeOp->validate( $form );
		if ( !$result->isValid() ) {
			$this->errorReporter->dieException(
				new ChangeOpValidationException( $result ),
				'modification-failed'
			);
		}

		$summary = new Summary();
		try {
			$changeOp->apply( $form, $summary );
		} catch ( ChangeOpException $exception ) {
			$this->errorReporter->dieException( $exception,  'unprocessable-request' );
		}

		$summaryString = $this->summaryFormatter->formatSummary( $summary );

		$status = $this->saveForm( $form, $summaryString, $baseRevId, $params );

		if ( !$status->isOK() ) {
			$this->dieStatus( $status );
		}

		$this->generateResponse( $form, $status, $params );
	}

	private function saveForm(
		Form $form,
		string $summary,
		int $baseRevisionId,
		array $params
	): EditEntityStatus {
		$editEntity = $this->editEntityFactory->newEditEntity(
			$this->getContext(),
			$form->getId(),
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
			$form,
			$summary,
			$flags,
			$tokenThatDoesNotNeedChecking,
			null,
			$params['tags'] ?: []
		);
	}

	private function generateResponse( Form $form, EditEntityStatus $status, array $params ): void {
		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, null );
		$this->resultBuilder->markSuccess();

		$serializedForm = $this->formSerializer->serialize( $form );
		$this->getResult()->addValue( null, 'form', $serializedForm );

		$this->resultBuilder->addTempUser( $status, fn ( $user ) => $this->getTempUserRedirectUrl( $params, $user ) );
	}

	protected function getAllowedParams(): array {
		return array_merge( [
			EditFormElementsRequestParser::PARAM_FORM_ID => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			EditFormElementsRequestParser::PARAM_DATA => [
				ParamValidator::PARAM_TYPE => 'text',
				ParamValidator::PARAM_REQUIRED => true,
			],
			EditFormElementsRequestParser::PARAM_BASEREVID => [
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
		$formId = 'L12-F1';
		$exampleData = [
			'representations' => [
				'en-US' => [ 'value' => 'color', 'language' => 'en-US' ],
				'en-GB' => [ 'value' => 'colour', 'language' => 'en-GB' ],
			],
			'grammaticalFeatures' => [
				'Q1', 'Q2',
			],
		];

		$query = http_build_query( [
			'action' => $this->getModuleName(),
			EditFormElementsRequestParser::PARAM_FORM_ID => $formId,
			EditFormElementsRequestParser::PARAM_DATA => json_encode( $exampleData ),
		] );

		$languages = array_column( $exampleData['representations'], 'language' );
		$representations = array_column( $exampleData['representations'], 'value' );

		$representationsText = $this->getLanguage()->commaList( $representations );
		$languagesText = $this->getLanguage()->commaList( $languages );
		$grammaticalFeaturesText = $this->getLanguage()->commaList( $exampleData['grammaticalFeatures'] );

		$exampleMessage = new \Message(
			'apihelp-wbleditformelements-example-1',
			[
				$formId,
				$representationsText,
				$languagesText,
				$grammaticalFeaturesText,
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
