<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Api;

use ApiBase;
use ApiCreateTempUserTrait;
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
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeNotFound;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\Serialization\SenseSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\Api\ResultBuilder;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\SummaryFormatter;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddSense extends ApiBase {

	use ApiCreateTempUserTrait;

	private const LATEST_REVISION = 0;

	private AddSenseRequestParser $requestParser;

	private ResultBuilder $resultBuilder;
	private ApiErrorReporter $errorReporter;
	private SenseSerializer $senseSerializer;
	private MediaWikiEditEntityFactory $editEntityFactory;
	private SummaryFormatter $summaryFormatter;
	private EntityRevisionLookup $entityRevisionLookup;

	/**
	 * @return self
	 */
	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ApiHelperFactory $apiHelperFactory,
		SerializerFactory $baseDataModelSerializerFactory,
		ChangeOpFactoryProvider $changeOpFactoryProvider,
		MediaWikiEditEntityFactory $editEntityFactory,
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
			$apiHelperFactory
		);
	}

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		AddSenseRequestParser $requestParser,
		SenseSerializer $senseSerializer,
		EntityRevisionLookup $entityRevisionLookup,
		MediaWikiEditEntityFactory $editEntityFactory,
		SummaryFormatter $summaryFormatter,
		ApiHelperFactory $apiHelperFactory
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->resultBuilder = $apiHelperFactory->getResultBuilder( $this );
		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
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
	public function execute(): void { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
		/*
		 * {
			  "glosses": [
				"en-GB": {
				  "value": "colour",
				  "language": "en-gb"
				},
				"en-US": {
				  "value": "color",
				  "language": "en-us"
				}
			  ]
			}
		 *
		 */

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
			$this->errorReporter->dieException( $exception, 'unprocessable-request' );
		}

		$baseRevId = $request->getBaseRevId() ?: $lexemeRevision->getRevisionId();

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
			$this->dieStatus( $status );
		}

		$entityRevision = $status->getRevision();

		/** @var Lexeme $editedLexeme */
		$editedLexeme = $entityRevision->getEntity();
		'@phan-var Lexeme $editedLexeme';
		$newSense = $this->getSenseWithMaxId( $editedLexeme );
		$serializedSense = $this->senseSerializer->serialize( $newSense );

		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, null );
		$this->resultBuilder->markSuccess();
		$this->resultBuilder->addTempUser( $status, fn ( $user ) => $this->getTempUserRedirectUrl( $params, $user ) );
		$this->getResult()->addValue( null, 'sense', $serializedSense );
	}

	/** @inheritDoc */
	protected function getAllowedParams(): array {
		return array_merge( [
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
			'glosses' => [
				'en-us' => [ 'value' => 'Some text value', 'language' => 'en-us' ],
				'en-gb' => [ 'value' => 'Another text value', 'language' => 'en-gb' ],
			],
		];

		$query = http_build_query( [
			'action' => $this->getModuleName(),
			AddSenseRequestParser::PARAM_LEXEME_ID => $lexemeId,
			AddSenseRequestParser::PARAM_DATA => json_encode( $exampleData ),
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
				$languagesText,
			]
		);

		return [
			urldecode( $query ) => $exampleMessage,
		];
	}

	private function getSenseWithMaxId( Lexeme $lexeme ): Sense {
		// TODO: This is all rather nasty
		$maxIdNumber = $lexeme->getSenses()->maxSenseIdNumber();
		// TODO: Use some service to get the ID object!
		$senseId = new SenseId( $lexeme->getId() . '-S' . $maxIdNumber );
		return $lexeme->getSense( $senseId );
	}

}
