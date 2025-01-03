<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki;

use MediaWiki\Api\ApiUsageException;
use MediaWiki\Api\IApiMessage;
use MediaWiki\Tests\User\TempUser\TempUserTestTrait;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Tests\Api\WikibaseApiTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * todo Add a reset function to wikibase Entity(Revision)Lookup to reset caches
 * Shared caches can lead to combinations fatal in tests but impossible in production
 * (e.g. change of a property entity type within the same process)
 *
 * @license GPL-2.0-or-later
 */
abstract class WikibaseLexemeApiTestCase extends WikibaseApiTestCase {

	use TempUserTestTrait;

	protected EntityStore $entityStore;

	protected function setUp(): void {
		parent::setUp();

		$this->entityStore = WikibaseRepo::getEntityStore();
	}

	/**
	 * Check that the query with params result in an ApiUsageException with given properties
	 *
	 * In addition to WikibaseApiTestCase::doTestQueryExceptions() capabilities it allows asserting
	 * ApiMessageTrait::getApiData() content
	 */
	public function doTestQueryApiException( array $params, array $exception ): void {
		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'No API error was raised' );
		} catch ( ApiUsageException $e ) {
			$status = $e->getStatusValue();
			/** @var IApiMessage $message */
			$message = $status->getMessages()[0];

			$this->assertInstanceOf( IApiMessage::class, $message );

			if ( array_key_exists( 'key', $exception ) ) {
				$this->assertStatusError( $exception['key'], $status );
			}

			if ( array_key_exists( 'params', $exception ) ) {
				$this->assertEquals(
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

	public function saveEntity( EntityDocument $entity ): void {
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
	protected function assertStatementGuidHasEntityId( string $serializedEntityId, string $guid ): void {
		$this->assertStringStartsWith(
			$serializedEntityId . StatementGuid::SEPARATOR,
			$guid
		);
	}

	/** Run a test to ensure that temp user redirects are correctly returned. */
	protected function doTestTempUserCreatedRedirect( array $params ): void {
		$this->enableAutoCreateTempUser();
		$this->setTemporaryHook( 'TempUserCreatedRedirect', function (
			$session,
			$user,
			$returnTo,
			$returnToQuery,
			$returnToAnchor,
			&$redirectUrl
		) {
			$this->assertSame( 'Lexeme:L1', $returnTo );
			$this->assertSame( 'b=c', $returnToQuery );
			$this->assertSame( '#d', $returnToAnchor );
			$redirectUrl = 'https://example.com/Lexeme:L1?b=c#d';
		} );

		[ $result ] = $this->doApiRequestWithToken( array_merge( $params, [
			'returnto' => 'Lexeme:L1',
			'returntoquery' => 'b=c',
			'returntoanchor' => 'd',
		] ), null, $this->getServiceContainer()->getUserFactory()->newAnonymous() );

		$this->assertArrayHasKey( 'tempusercreated', $result );
		$this->assertSame( 'https://example.com/Lexeme:L1?b=c#d', $result['tempuserredirect'] );
	}

}
