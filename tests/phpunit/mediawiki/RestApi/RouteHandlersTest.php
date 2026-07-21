<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\RestApi;

use Generator;
use LogicException;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Throwable;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemma;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Interactors\GetLexeme\GetLexeme;
use Wikibase\Lexeme\Interactors\GetLexeme\GetLexemeResponse;
use Wikibase\Lexeme\Interactors\GetLexeme\LexemeRedirect;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Repo\RestApi\Middleware\UnexpectedErrorHandlerMiddleware;

/**
 * @coversNothing
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0-or-later
 */
class RouteHandlersTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	private static array $routes = [];

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$routes = json_decode(
			file_get_contents( __DIR__ . '/../../../../src/MediaWiki/RestApi/routes.dev.json' ),
			true
		);
	}

	/**
	 * @dataProvider routeHandlersProvider
	 */
	public function testSuccess( array $routeHandler ): void {
		$useCase = $this->createStub( $routeHandler['useCase'] );
		$useCase->method( 'execute' )->willReturn( $routeHandler['useCaseResponse'] );
		$this->setService( $routeHandler['serviceName'], $useCase );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest(
			$this->getRouteForUseCase( $routeHandler['useCase'] ),
			$routeHandler['validRequest']
		)->execute();

		$this->assertThat(
			$response->getStatusCode(),
			$this->logicalAnd( $this->greaterThanOrEqual( 200 ), $this->lessThan( 300 ) )
		);
		$this->assertSame( [ 'application/json' ], $response->getHeader( 'Content-Type' ) );
	}

	/**
	 * @dataProvider routeHandlersProvider
	 */
	public function testHandlesUnexpectedErrors( array $routeHandler ): void {
		$useCase = $this->createStub( $routeHandler['useCase'] );
		$useCase->method( 'execute' )->willThrowException( new RuntimeException() );
		$this->setService( $routeHandler['serviceName'], $useCase );
		$this->setService( 'WikibaseLexeme.ErrorReporter', $this->createStub( ErrorReporter::class ) );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest(
			$this->getRouteForUseCase( $routeHandler['useCase'] ),
			$routeHandler['validRequest']
		)->execute();

		$this->assertSame( 500, $response->getStatusCode() );
		$responseBody = json_decode( $response->getBody()->getContents() );
		$this->assertSame( UnexpectedErrorHandlerMiddleware::ERROR_CODE, $responseBody->code );
	}

	/**
	 * @dataProvider routeHandlersProvider
	 */
	public function testReadWriteAccess( array $routeHandler ): void {
		$routeData = $this->getRouteForUseCase( $routeHandler['useCase'] );
		$routeHandler = $this->newHandlerWithValidRequest( $routeData, $routeHandler['validRequest'] );

		$this->assertTrue( $routeHandler->needsReadAccess() );
		$this->assertSame( $routeData['method'] !== 'GET', $routeHandler->needsWriteAccess() );
	}

	/**
	 * @dataProvider routeHandlersProvider
	 */
	public function testHandlesExpectedExceptions( array $routeHandler ): void {
		foreach ( $routeHandler['expectedExceptions'] as [ $error, $assertExpectedResponse ] ) {
			$assertExpectedResponse( $this->getHttpResponseForThrowingUseCase( $routeHandler, $error ) );
		}
	}

	public static function routeHandlersProvider(): Generator {
		$lastModified = '20260731042031';
		$hasHttpStatus = fn ( int $status ) => fn ( Response $r ) => self::assertSame( $status, $r->getStatusCode() );
		$hasErrorCode = fn ( string $errorCode ) => function ( Response $response ) use ( $errorCode ): void {
			self::assertSame( $errorCode, json_decode( (string)$response->getBody() )->code );
			self::assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		}; // phpcs:ignore -- phpcs doesn't like the semicolon here, but it's very much needed.

		yield 'GetLexeme' => [ [
			'useCase' => GetLexeme::class,
			'useCaseResponse' => new GetLexemeResponse(
				new Lexeme(
					new LexemeId( 'L1' ),
					new Lemmas(
						new Lemma( 'en-ca', 'colour' ),
						new Lemma( 'en-us', 'color' )
					)
				),
				42,
				$lastModified
			),
			'serviceName' => 'WikibaseLexeme.GetLexeme',
			'validRequest' => [ 'pathParams' => [ 'lexeme_id' => 'L1' ] ],
			'expectedExceptions' => [
				[ UseCaseError::newLexemeNotFound(), $hasErrorCode( UseCaseError::LEXEME_NOT_FOUND ) ],
				[ new LexemeRedirect( new LexemeId( 'L2' ) ), $hasHttpStatus( 308 ) ],
			],
		] ];
	}

	private function getHttpResponseForThrowingUseCase( array $routeHandler, Throwable $error ): Response {
		$useCase = $this->createStub( $routeHandler['useCase'] );
		$useCase->method( 'execute' )->willThrowException( $error );
		$this->setService( $routeHandler['serviceName'], $useCase );

		return $this->newHandlerWithValidRequest(
			$this->getRouteForUseCase( $routeHandler['useCase'] ),
			$routeHandler['validRequest']
		)->execute();
	}

	private function newHandlerWithValidRequest( array $routeData, array $validRequest ): Handler {
		$routeHandler = $routeData['factory']();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'method' => $routeData['method'],
				'headers' => [
					'User-Agent' => 'PHPUnit Test',
					'Content-Type' => 'application/json',
				],
				'pathParams' => $validRequest['pathParams'],
			] ),
			[ 'path' => $routeData['path'] ]
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}

	private function getRouteForUseCase( string $useCaseClass ): array {
		$classNameParts = explode( '\\', $useCaseClass );
		$useCaseName = end( $classNameParts );

		foreach ( self::$routes as $route ) {
			if ( str_contains( $route['factory'], "\\{$useCaseName}RouteHandler" ) ) {
				return $route;
			}
		}

		throw new LogicException( "No route found for use case $useCaseName" );
	}

}
