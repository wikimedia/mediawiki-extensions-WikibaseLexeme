<?php

namespace Wikibase\Lexeme\Api;

use ApiBase;
use ApiMain;
use Wikibase\Lexeme\DataModel\Serialization\FormSerializer;
use Wikibase\Repo\Api\EntitySavingHelper;
use Wikibase\Summary;

class AddForm extends ApiBase {

	/**
	 * @var EntitySavingHelper
	 */
	private $entitySavingHelper;

	/**
	 * @var AddFormRequestParser
	 */
	private $requestParser;

	/**
	 * @var FormSerializer
	 */
	private $formSerializer;

	/**
	 * @return AddForm
	 */
	public static function newFromGlobalState( \ApiMain $mainModule, $moduleName ) {
		$wikibaseRepo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

		$serializerFactory = $wikibaseRepo->getBaseDataModelSerializerFactory();

		$formSerializer = new FormSerializer(
			$serializerFactory->newTermListSerializer(),
			$serializerFactory->newStatementListSerializer()
		);

		return new AddForm(
			$mainModule,
			$moduleName,
			new AddFormRequestParser( $wikibaseRepo->getEntityIdParser() ),
			$formSerializer,
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getEntitySavingHelper( $module );
			}
		);
	}

	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		AddFormRequestParser $requestParser,
		FormSerializer $formSerializer,
		callable $entitySavingHelperInstantiator
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->entitySavingHelper = $entitySavingHelperInstantiator( $this );
		$this->requestParser = $requestParser;
		$this->formSerializer = $formSerializer;
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
				{
				  "representation": "",
				  "language": ""
				},
				{
				  "representation": "",
				  "language": ""
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

		$parserResult = $this->requestParser->parse( $this->extractRequestParams() );

		if ( $parserResult->hasErrors() ) {
			//TODO: Increase stats counter on failure
			$this->dieStatus( $parserResult->asFatalStatus() );
		}

		$request = $parserResult->getRequest();

		$lexeme = $this->entitySavingHelper->loadEntity( $request->getLexemeId() );
		$newForm = $request->addFormTo( $lexeme );
		$summary = new AddFormSummary( $lexeme->getId(), $newForm );
		//FIXME: Handle failure
		//FIXME: ACHTUNG! attemptSaveEntity() uses 'baserevid' internally which should not be used!
		$status = $this->entitySavingHelper->attemptSaveEntity( $lexeme, $summary );

		$apiResult = $this->getResult();

		$serializedForm = $this->formSerializer->serialize( $newForm );

		$apiResult->addValue( null, 'success', 1 );
		$apiResult->addValue( null, 'form', $serializedForm );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			[
				'lexemeId' => [
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				],
				'data' => [
					self::PARAM_TYPE => 'text',
					self::PARAM_REQUIRED => true,
				],
				'token' => null,
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
				[ 'representation' => 'color', 'language' => 'en-US' ],
				[ 'representation' => 'colour', 'language' => 'en-GB' ],
			],
			'grammaticalFeatures' => [
				'Q1', 'Q2'
			]
		];

		$query = http_build_query( [
			'action' => $this->getModuleName(),
			'lexemeId' => $lexemeId,
			'data' => json_encode( $exampleData )
		] );

		$languages = array_map( function ( $r ) {
			return $r['language'];
		}, $exampleData['representations'] );
		$representations = array_map( function ( $r ) {
			return $r['representation'];
		}, $exampleData['representations'] );

		$representationsText = $this->getLanguage()->commaList( $representations );
		$languagesText = $this->getLanguage()->commaList( $languages );
		$grammaticalFeaturesText = $this->getLanguage()->commaList( $exampleData['grammaticalFeatures'] );

		$exampleMessage = new \Message(
			'apihelp-wblexemeaddform-example-1',
			[
				$lexemeId,
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
