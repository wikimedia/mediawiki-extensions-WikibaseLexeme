<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use ApiBase;
use ApiMain;
use Deserializers\Deserializer;
use LogicException;
use RuntimeException;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\Domain\Model\Exceptions\ConflictException;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeNotFound;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\Serialization\SenseSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\SummaryFormatter;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddSense extends ApiBase {

	private const LATEST_REVISION = 0;

	/**
	 * @var AddSenseRequestParser
	 */
	private $requestParser;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var SenseSerializer
	 */
	private $senseSerializer;

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

	/**
	 * @return self
	 */
	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ApiHelperFactory $apiHelperFactory,
		SerializerFactory $baseDataModelSerializerFactory,
		ChangeOpFactoryProvider $changeOpFactoryProvider,
		MediawikiEditEntityFactory $editEntityFactory,
		EntityIdParser $entityIdParser,
		Deserializer $externalFormatStatementDeserializer,
		Store $store,
		StringNormalizer $stringNormalizer,
		SummaryFormatter $summaryFormatter
	) {
		$senseSerializer = new SenseSerializer(
			$baseDataModelSerializerFactory->newTermListSerializer(),
			$baseDataModelSerializerFactory->newStatementListSerializer()
		);

		return new self(
			$mainModule,
			$moduleName,
			new AddSenseRequestParser(
				$entityIdParser,
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
			$senseSerializer,
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
		AddSenseRequestParser $requestParser,
		SenseSerializer $senseSerializer,
		EntityRevisionLookup $entityRevisionLookup,
		MediawikiEditEntityFactory $editEntityFactory,
		SummaryFormatter $summaryFormatter,
		callable $errorReporterInstantiator
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->errorReporter = $errorReporterInstantiator( $this );
		$this->requestParser = $requestParser;
		$this->senseSerializer = $senseSerializer;
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
			  "glosses": [
				"en-GB": {
				  "value": "colour",
				  "language": "en-GB"
				},
				"en-US": {
				  "value": "color",
				  "language": "en-US"
				}
			  ]
			}
		 *
		 */

		//FIXME: Response structure? - Added sense

		//TODO: Corresponding HTTP codes on failure (e.g. 400, 404, 422) (?)
		//TODO: Documenting response structure. Is it possible?

		$params = $this->extractRequestParams();
		$request = $this->requestParser->parse( $params );

		try {
			$lexemeId = $request->getLexemeId();
			$lexemeRevision = $this->entityRevisionLookup->getEntityRevision(
				$lexemeId,
				self::LATEST_REVISION,
				LookupConstants::LATEST_FROM_MASTER
			);

			if ( !$lexemeRevision ) {
				$error = new LexemeNotFound( $lexemeId );
				$this->dieWithError( $error->asApiMessage( AddSenseRequestParser::PARAM_LEXEME_ID,
					[] ) );
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

		if ( $request->getBaseRevId() ) {
			$baseRevId = $request->getBaseRevId();
		} else {
			$baseRevId = $lexemeRevision->getRevisionId();
		}

		$editEntity = $this->editEntityFactory->newEditEntity(
			$this->getContext(),
			$request->getLexemeId(),
			$baseRevId
		);
		$summaryString = $this->summaryFormatter->formatSummary(
			$summary
		);
		$flags = EDIT_UPDATE;
		if ( isset( $params['bot'] ) && $params['bot'] &&
			$this->getPermissionManager()->userHasRight( $this->getUser(), 'bot' )
		) {
			$flags |= EDIT_FORCE_BOT;
		}

		$tokenThatDoesNotNeedChecking = false;
		// FIXME: Handle failure
		try {
			$status = $editEntity->attemptSave(
				$lexeme,
				$summaryString,
				$flags,
				$tokenThatDoesNotNeedChecking,
				null,
				$params['tags'] ?: []
			);
		} catch ( ConflictException $exception ) {
			$this->dieWithException( new RuntimeException( 'Edit conflict: ' . $exception->getMessage() ) );
		}

		if ( !$status->isGood() ) {
			$this->dieStatus( $status ); // Seems like it is good enough
		}

		/** @var EntityRevision $entityRevision */
		$entityRevision = $status->getValue()['revision'];
		$revisionId = $entityRevision->getRevisionId();

		/** @var Lexeme $editedLexeme */
		$editedLexeme = $entityRevision->getEntity();
		$newSense = $this->getSenseWithMaxId( $editedLexeme );
		$serializedSense = $this->senseSerializer->serialize( $newSense );

		$apiResult = $this->getResult();
		$apiResult->addValue( null, 'lastrevid', $revisionId );
		// TODO: Do we really need `success` property in response?
		$apiResult->addValue( null, 'success', 1 );
		$apiResult->addValue( null, 'sense', $serializedSense );
	}

	/** @inheritDoc */
	protected function getAllowedParams() {
		return array_merge(
			[
				AddSenseRequestParser::PARAM_LEXEME_ID => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => true,
				],
				AddSenseRequestParser::PARAM_DATA => [
					ParamValidator::PARAM_TYPE => 'text',
					ParamValidator::PARAM_REQUIRED => true,
				],
				AddSenseRequestParser::PARAM_BASEREVID => [
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
			'glosses' => [
				'en-US' => [ 'value' => 'Some text value', 'language' => 'en-US' ],
				'en-GB' => [ 'value' => 'Another text value', 'language' => 'en-GB' ],
			]
		];

		$query = http_build_query( [
			'action' => $this->getModuleName(),
			AddSenseRequestParser::PARAM_LEXEME_ID => $lexemeId,
			AddSenseRequestParser::PARAM_DATA => json_encode( $exampleData )
		] );

		$languages = array_column( $exampleData['glosses'], 'language' );
		$glosses = array_column( $exampleData['glosses'], 'value' );

		$glossesText = $this->getLanguage()->commaList( $glosses );
		$languagesText = $this->getLanguage()->commaList( $languages );

		$exampleMessage = new \Message(
			'apihelp-wbladdsense-example-1',
			[
				$lexemeId,
				$glossesText,
				$languagesText
			]
		);

		return [
			urldecode( $query ) => $exampleMessage
		];
	}

	private function getSenseWithMaxId( Lexeme $lexeme ) {
		// TODO: This is all rather nasty
		$maxIdNumber = $lexeme->getSenses()->maxSenseIdNumber();
		// TODO: Use some service to get the ID object!
		$senseId = new SenseId( $lexeme->getId() . '-S' . $maxIdNumber );
		return $lexeme->getSense( $senseId );
	}

}
