<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use ApiMessage;
use ApiUsageException;
use MediaWiki\Services\ServiceContainer;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Tests\Api\WikibaseApiTestCase;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\BufferingTermLookup;

/**
 * @license GPL-2.0-or-later
 */
abstract class WikibaseLexemeApiTestCase extends WikibaseApiTestCase {

	// TODO add reset function to wikibase EntityRevisionLookup
	const ENTITY_REVISION_LOOKUP_UNCACHED = 'uncached';

	/**
	 * @var WikibaseRepo
	 */
	protected $wikibaseRepo;

	/**
	 * @var EntityStore
	 */
	protected $entityStore;

	public function setUp() {
		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'revision';

		parent::setUp();

		$this->wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$this->entityStore = $this->wikibaseRepo->getEntityStore();
	}

	public function tearDown() {
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
			/** @var ApiMessage $message */
			$message = $e->getMessageObject();

			$this->assertInstanceOf( ApiMessage::class, $message );

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

	private function resetTermBuffer() {
		/**
		 * @var ServiceContainer $services
		 */
		$services = $this->wikibaseRepo->getWikibaseServices();
		$services->disableService( 'TermBuffer' );
		$services->redefineService( 'TermBuffer', function () {
			return new BufferingTermLookup( $this->wikibaseRepo->getStore()->getTermIndex(), 1000 );
		} );
	}

}
