<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use ApiMain;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Lexeme\MediaWiki\Api\Error\SenseNotFound;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Serialization\SenseSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class EditSenseElements extends \ApiBase {

	const LATEST_REVISION = 0;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var MediawikiEditEntityFactory
	 */
	private $editEntityFactory;

	/**
	 * @var EditSenseElementsRequestParser
	 */
	private $requestParser;

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var SenseSerializer
	 */
	private $senseSerializer;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	public static function newFromGlobalState( ApiMain $mainModule, $moduleName ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

		$serializerFactory = $wikibaseRepo->getBaseDataModelSerializerFactory();

		$senseSerializer = new SenseSerializer(
			$serializerFactory->newTermListSerializer(),
			$serializerFactory->newStatementListSerializer()
		);

		return new self(
			$mainModule,
			$moduleName,
			$wikibaseRepo->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			$wikibaseRepo->newEditEntityFactory( $mainModule->getContext() ),
			new EditSenseElementsRequestParser(
				new SenseIdDeserializer( $wikibaseRepo->getEntityIdParser() ),
				new EditSenseChangeOpDeserializer(
					new GlossesChangeOpDeserializer(
						new TermDeserializer(),
						$wikibaseRepo->getStringNormalizer(),
						new LexemeTermSerializationValidator(
							new LexemeTermLanguageValidator( WikibaseLexemeServices::getTermLanguages() )
						)
					)
				)
			),
			$wikibaseRepo->getSummaryFormatter(),
			$senseSerializer,
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getErrorReporter( $module );
			},
			$wikibaseRepo->getEntityStore()
		);
	}

	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		EntityRevisionLookup $entityRevisionLookup,
		MediawikiEditEntityFactory $editEntityFactory,
		EditSenseElementsRequestParser $requestParser,
		SummaryFormatter $summaryFormatter,
		SenseSerializer $senseSerializer,
		callable $errorReporterInstantiator,
		EntityStore $entityStore
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->editEntityFactory = $editEntityFactory;
		$this->requestParser = $requestParser;
		$this->summaryFormatter = $summaryFormatter;
		$this->senseSerializer = $senseSerializer;
		$this->errorReporter = $errorReporterInstantiator( $this );
		$this->entityStore = $entityStore;
	}

	public function execute() {
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
			EntityRevisionLookup::LATEST_FROM_MASTER
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

		$this->generateResponse( $sense );
	}

	/**
	 * @param Sense $sense
	 * @param string $summary
	 * @param int $baseRevisionId
	 * @param array $params
	 * @return \Status
	 */
	private function saveSense(
		Sense $sense,
		$summary,
		$baseRevisionId,
		array $params
	) {
		$editEntity = $this->editEntityFactory->newEditEntity(
			$this->getUser(),
			$sense->getId(),
			$baseRevisionId
		);

		// TODO: bot flag should probably be part of the request
		$flags = EDIT_UPDATE;
		if ( isset( $params['bot'] ) && $params['bot'] && $this->getUser()->isAllowed( 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		$tokenThatDoesNotNeedChecking = false;
		return $editEntity->attemptSave(
			$sense,
			$summary,
			$flags,
			$tokenThatDoesNotNeedChecking
		);
	}

	/**
	 * @param Sense $sense
	 */
	private function generateResponse( Sense $sense ) {
		$apiResult = $this->getResult();

		$serializedSense = $this->senseSerializer->serialize( $sense );
		unset( $serializedSense['claims'] );

		// TODO: Do we really need `success` property in response?
		$apiResult->addValue( null, 'success', 1 );
		$apiResult->addValue( null, 'sense', $serializedSense );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return [
			EditSenseElementsRequestParser::PARAM_SENSE_ID => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			],
			EditSenseElementsRequestParser::PARAM_DATA => [
				self::PARAM_TYPE => 'text',
				self::PARAM_REQUIRED => true,
			],
			'bot' => [
				self::PARAM_TYPE => 'boolean',
				self::PARAM_DFLT => false,
			],
			EditSenseElementsRequestParser::PARAM_BASEREVID => [
				self::PARAM_TYPE => 'integer',
			]
		];
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
				]
			],
		];

		$query = http_build_query( [
			'action' => $this->getModuleName(),
			EditSenseElementsRequestParser::PARAM_SENSE_ID => $senseId,
			EditSenseElementsRequestParser::PARAM_DATA => json_encode( $exampleData )
		] );

		$languages = array_map( function ( $r ) {
			return $r['language'];
		}, $exampleData['glosses'] );
		$glosses = array_map( function ( $r ) {
			return $r['value'];
		}, $exampleData['glosses'] );

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
			urldecode( $query ) => $exampleMessage
		];
	}

	/**
	   Returns $latestRevisionId if all of edits since $baseRevId are done
	 * by the same user, otherwise returns $baseRevId.
	 *
	 * @param int $latestRevisionId
	 * @param int $baseRevId
	 * @param EntityId $entityId
	 * @return int
	 */
	private function getRevIdForWhenUserWasLastToEdit(
		$latestRevisionId,
		$baseRevId,
		EntityId $entityId
	) {
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
