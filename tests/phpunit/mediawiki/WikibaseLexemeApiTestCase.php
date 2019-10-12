<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use ApiUsageException;
use IApiMessage;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Tests\Api\WikibaseApiTestCase;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\BufferingTermLookup;
use Wikimedia\Services\ServiceContainer;

/**
 * todo Add a reset function to wikibase Entity(Revision)Lookup to reset caches
 * Shared caches can lead to combinations fatal in tests but impossible in production
 * (e.g. change of a property entity type within the same process)
 *
 * @license GPL-2.0-or-later
 */
abstract class WikibaseLexemeApiTestCase extends WikibaseApiTestCase {

	/**
	 * @var WikibaseRepo
	 */
	protected $wikibaseRepo;

	/**
	 * @var EntityStore
	 */
	protected $entityStore;

	public function setUp() : void {
		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'revision';

		parent::setUp();

		$this->wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$this->entityStore = $this->wikibaseRepo->getEntityStore();
	}

	public function tearDown() : void {
		parent::tearDown();
		$this->resetTermBuffer();
	}

	/**
	 * Check that the query with params result in an ApiUsageException with given properties
	 *
	 * In addition to WikibaseApiTestCase::doTestQueryExceptions() capabilities it allows asserting
	 * ApiMessageTrait::getApiData() content
	 *
	 * @param array $params
	 * @param array $exception
	 */
	public function doTestQueryApiException( array $params, array $exception ) {
		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'No API error was raised' );
		} catch ( ApiUsageException $e ) {
			/** @var IApiMessage $message */
			$message = $e->getMessageObject();

			$this->assertInstanceOf( IApiMessage::class, $message );

			if ( array_key_exists( 'key', $exception ) ) {
				$this->assertSame(
					$exception['key'],
					$message->getKey(),
					'Wrong message key'
				);
			}

			if ( array_key_exists( 'params', $exception ) ) {
				$this->assertSame(
					$exception['params'],
					$message->getParams(),
					'Wrong message parameters'
				);
			}

			if ( array_key_exists( 'code', $exception ) ) {
				$this->assertSame(
					$exception['code'],
					$message->getApiCode(),
					'Wrong api code'
				);
			}

			if ( array_key_exists( 'data', $exception ) ) {
				$this->assertSame(
					$exception['data'],
					$message->getApiData(),
					'Wrong api data'
				);
			}
		}
	}

	public function saveEntity( EntityDocument $entity ) {
		$this->entityStore->saveEntity(
			$entity,
			static::class,
			$this->getTestUser()->getUser()
		);
	}

	/**
	 * @param string $serializedEntityId
	 * @param string $guid GUID of a statement
	 */
	protected function assertStatementGuidHasEntityId( $serializedEntityId, $guid ) {
		$this->assertStringStartsWith(
			$serializedEntityId . StatementGuid::SEPARATOR,
			$guid
		);
	}

	private function resetTermBuffer() {
		/**
		 * @var ServiceContainer $services
		 */
		$services = $this->wikibaseRepo->getWikibaseServices();
		if ( $services->hasService( 'TermBuffer' ) ) {
			$services->disableService( 'TermBuffer' );
			$services->redefineService( 'TermBuffer', function () {
				return new BufferingTermLookup(
					$this->wikibaseRepo->getStore()->getTermIndex(),
					1000
				);
			} );
		} else {
			$services->defineService( 'TermBuffer', function () {
				return new BufferingTermLookup(
					$this->wikibaseRepo->getStore()->getTermIndex(),
					1000
				);
			} );
		}
	}

}
