<?php

namespace Wikibase\Lexeme\Api;

use ApiMain;
use Wikibase\EditEntityFactory;
use Wikibase\Lexeme\Api\Error\FormNotFound;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\Serialization\FormSerializer;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class EditFormElements extends \ApiBase {

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EditEntityFactory
	 */
	private $editEntityFactory;

	/**
	 * @var EditFormElementsRequestParser
	 */
	private $requestParser;

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var FormSerializer
	 */
	private $formSerializer;

	public static function newFromGlobalState( ApiMain $mainModule, $moduleName ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$serializerFactory = $wikibaseRepo->getBaseDataModelSerializerFactory();

		$formSerializer = new FormSerializer(
			$serializerFactory->newTermListSerializer(),
			$serializerFactory->newStatementListSerializer()
		);

		return new self(
			$mainModule,
			$moduleName,
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$wikibaseRepo->newEditEntityFactory( $mainModule->getContext() ),
			new EditFormElementsRequestParser( $wikibaseRepo->getEntityIdParser() ),
			$wikibaseRepo->getSummaryFormatter(),
			$formSerializer
		);
	}

	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		EntityRevisionLookup $entityRevisionLookup,
		EditEntityFactory $editEntityFactory,
		EditFormElementsRequestParser $requestParser,
		SummaryFormatter $summaryFormatter,
		FormSerializer $formSerializer
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->editEntityFactory = $editEntityFactory;
		$this->requestParser = $requestParser;
		$this->summaryFormatter = $summaryFormatter;
		$this->formSerializer = $formSerializer;
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$parserResult = $this->requestParser->parse( $params );

		if ( $parserResult->hasErrors() ) {
			//TODO: Increase stats counter on failure
			// `wikibase.repo.api.errors.total` counter
			$this->dieStatus( $parserResult->asFatalStatus() );
		}

		$request = $parserResult->getRequest();
		$formId = $request->getFormId();

		$latestRevision = 0;
		$formRevision = $this->entityRevisionLookup->getEntityRevision(
			$formId,
			$latestRevision,
			EntityRevisionLookup::LATEST_FROM_MASTER
		);

		if ( $formRevision === null ) {
			$error = new FormNotFound( $formId );
			$this->dieWithError( $error->asApiMessage() );
		}
		$form = $formRevision->getEntity();

		$changeOp = $request->getChangeOp();
		// TODO: Uses some FormattableSummary instead of Summary
		$summary = $changeOp->generateSummaryWhenAppliedToForm( $form );
		$changeOp->apply( $form );

		$summaryString = $this->summaryFormatter->formatSummary( $summary );

		$status = $this->saveForm( $form, $summaryString, $formRevision->getRevisionId(), $params );

		if ( !$status->isGood() ) {
			$this->dieStatus( $status );
		}

		$this->generateResponse( $form );
	}

	/**
	 * @param Form $form
	 * @param string $summary
	 * @param int $baseRevisionId
	 * @param array $params
	 * @return \Status
	 */
	private function saveForm(
		Form $form,
		$summary,
		$baseRevisionId,
		array $params
	) {
		$editEntity = $this->editEntityFactory->newEditEntity(
			$this->getUser(),
			$form->getId(),
			$baseRevisionId
		);

		// TODO: bot flag should probably be part of the request
		$flags = EDIT_UPDATE;
		if ( isset( $params['bot'] ) && $params['bot'] && $this->getUser()->isAllowed( 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		$tokenThatDoesNotNeedChecking = false;
		return $editEntity->attemptSave(
			$form,
			$summary,
			$flags,
			$tokenThatDoesNotNeedChecking
		);
	}

	/**
	 * @param Form $form
	 */
	private function generateResponse( Form $form ) {
		$apiResult = $this->getResult();

		$serializedForm = $this->formSerializer->serialize( $form );
		unset( $serializedForm['claims'] );

		// TODO: Do we really need `success` property in response?
		$apiResult->addValue( null, 'success', 1 );
		$apiResult->addValue( null, 'form', $serializedForm );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return [
			'formId' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			],
			'data' => [
				self::PARAM_TYPE => 'text',
				self::PARAM_REQUIRED => true,
			],
			'bot' => [
				self::PARAM_TYPE => 'boolean',
				self::PARAM_DFLT => false,
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
		$formId = 'L12-F1';
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
			'formId' => $formId,
			'data' => json_encode( $exampleData )
		] );

		$languages = array_map( function ( $r ) {
			return $r['language'];
		}, $exampleData['representations'] );
		$representations = array_map( function ( $r ) {
			return $r['value'];
		}, $exampleData['representations'] );

		$representationsText = $this->getLanguage()->commaList( $representations );
		$languagesText = $this->getLanguage()->commaList( $languages );
		$grammaticalFeaturesText = $this->getLanguage()->commaList( $exampleData['grammaticalFeatures'] );

		$exampleMessage = new \Message(
			'apihelp-wbleditformelements-example-1',
			[
				$formId,
				$representationsText,
				$languagesText,
				$grammaticalFeaturesText
			]
		);

		return [
			$query => $exampleMessage
		];
	}

}
