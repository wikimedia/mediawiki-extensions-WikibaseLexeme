<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use ApiBase;
use ApiMain;
use LogicException;
use Wikibase\EditEntityFactory;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeNotFound;
use Wikibase\Lexeme\Domain\DataModel\FormId;
use Wikibase\Lexeme\Domain\DataModel\Lexeme;
use Wikibase\Lexeme\Domain\DataModel\Serialization\FormSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class AddForm extends ApiBase {

	const LATEST_REVISION = 0;

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
	public static function newFromGlobalState( \ApiMain $mainModule, $moduleName ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

		$serializerFactory = $wikibaseRepo->getBaseDataModelSerializerFactory();

		$formSerializer = new FormSerializer(
			$serializerFactory->newTermListSerializer(),
			$serializerFactory->newStatementListSerializer()
		);

		return new self(
			$mainModule,
			$moduleName,
			new AddFormRequestParser(
				$wikibaseRepo->getEntityIdParser(),
				WikibaseLexemeServices::getEditFormChangeOpDeserializer()
			),
			$formSerializer,
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
		AddFormRequestParser $requestParser,
		FormSerializer $formSerializer,
		EntityRevisionLookup $entityRevisionLookup,
		EditEntityFactory $editEntityFactory,
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

		try {
			$lexemeId = $request->getLexemeId();
			$lexemeRevision = $this->entityRevisionLookup->getEntityRevision(
				$lexemeId,
				self::LATEST_REVISION,
				EntityRevisionLookup::LATEST_FROM_MASTER
			);

			if ( !$lexemeRevision ) {
				$error = new LexemeNotFound( $lexemeId );
				$this->dieWithError( $error->asApiMessage( AddFormRequestParser::PARAM_LEXEME_ID, [] ) );
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
		/** @var Lexeme $lexeme */
		$lexeme = $lexemeRevision->getEntity();
		$changeOp = $request->getChangeOp();

		$summary = new Summary();
		$result = $changeOp->validate( $lexeme );
		if ( !$result->isValid() ) {
			$this->errorReporter->dieException(
				new ChangeOpValidationException( $result ),
				'modification-failed'
			);
		}

		try {
			$changeOp->apply( $lexeme, $summary );
		} catch ( ChangeOpException $exception ) {
			$this->errorReporter->dieException( $exception,  'unprocessable-request' );
		}

		$editEntity = $this->editEntityFactory->newEditEntity(
			$this->getUser(),
			$request->getLexemeId(),
			$lexemeRevision->getRevisionId()
		);
		$summaryString = $this->summaryFormatter->formatSummary(
			$summary
		);
		$flags = EDIT_UPDATE;
		if ( isset( $params['bot'] ) && $params['bot'] && $this->getUser()->isAllowed( 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		$tokenThatDoesNotNeedChecking = false;
		//FIXME: Handle failure
		$status = $editEntity->attemptSave(
			$lexeme,
			$summaryString,
			$flags,
			$tokenThatDoesNotNeedChecking
		);

		if ( !$status->isGood() ) {
			$this->dieStatus( $status ); //Seems like it is good enough
		}

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

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			[
				AddFormRequestParser::PARAM_LEXEME_ID => [
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				],
				AddFormRequestParser::PARAM_DATA => [
					self::PARAM_TYPE => 'text',
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

}
