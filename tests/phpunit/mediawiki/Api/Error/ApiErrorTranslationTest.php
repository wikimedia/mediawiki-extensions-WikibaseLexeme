<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api\Error;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\Api\Error\ApiError;
use Wikibase\Lexeme\MediaWiki\Api\Error\InvalidFormClaims;
use Wikibase\Lexeme\MediaWiki\Api\Error\InvalidItemId;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldIsNotAnItemId;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldIsRequired;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeNotFound;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeTermTextCanNotBeEmpty;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotLexemeId;
use Wikibase\Lexeme\MediaWiki\Api\Error\UnknownLanguage;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\Error\InvalidFormClaims
 * @covers \Wikibase\Lexeme\MediaWiki\Api\Error\InvalidItemId
 * @covers \Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType
 * @covers \Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldIsNotAnItemId
 * @covers \Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldIsRequired
 * @covers \Wikibase\Lexeme\MediaWiki\Api\Error\LexemeNotFound
 * @covers \Wikibase\Lexeme\MediaWiki\Api\Error\LexemeTermTextCanNotBeEmpty
 * @covers \Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotAJsonObject
 * @covers \Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotLexemeId
 * @covers \Wikibase\Lexeme\MediaWiki\Api\Error\UnknownLanguage
 *
 * @license GPL-2.0-or-later
 */
class ApiErrorTranslationTest extends TestCase {

	/**
	 * @dataProvider provideApiErrors
	 */
	public function testApiErrorsAreTranslated( ApiError $error, array $paramValues ) {
		$apiMessage = $error->asApiMessage( 'param-1', [ 'a', 1, 'b' ] );

		$this->assertInstanceOf( \ApiMessage::class, $apiMessage );
		$messageKey = $apiMessage->getKey();
		$this->assertTrue( $apiMessage->exists(), "Message '{$messageKey}' is not translated" );
		$this->assertEnglishTranslationContainsAllTheParameters( $apiMessage, $paramValues );
	}

	public function provideApiErrors() {
		return [
			ParameterIsNotAJsonObject::class => [
				new ParameterIsNotAJsonObject( 'param-1', 'given-param' ),
				[ 'param-1', 'given-param' ]
			],
			JsonFieldIsRequired::class => [
				new JsonFieldIsRequired( 'id' ),
				[ 'param-1', 'a/1/b' ]
			],
			JsonFieldIsNotAnItemId::class => [
				new JsonFieldIsNotAnItemId( 'foo' ),
				[ 'param-1', 'a/1/b', 'foo' ]
			],
			JsonFieldHasWrongType::class => [
				new JsonFieldHasWrongType( 'string', 'array' ),
				[ 'param-1', 'a/1/b', 'string', 'array' ]
			],
			ParameterIsNotLexemeId::class => [
				new ParameterIsNotLexemeId( 'foo' ),
				[ 'param-1', 'foo' ]
			],
			LexemeTermTextCanNotBeEmpty::class => [
				new LexemeTermTextCanNotBeEmpty(),
				[]
			],
			LexemeNotFound::class => [
				new LexemeNotFound( new LexemeId( 'L1' ) ),
				[ 'L1' ]
			],
			InvalidItemId::class => [
				new InvalidItemId( 'Qx' ),
				[ 'param-1', 'a/1/b', 'Qx' ]
			],
			UnknownLanguage::class => [
				new UnknownLanguage( 'foo' ),
				[ 'param-1', 'a/1/b', 'foo' ]
			],
			InvalidFormClaims::class => [
				new InvalidFormClaims(),
				[ 'param-1', 'a/1/b' ]
			],
		];
	}

	private function assertEnglishTranslationContainsAllTheParameters(
		\Message $apiMessage,
		array $paramValues
	) {
		$text = $apiMessage->inLanguage( 'en' )->text();
		foreach ( $paramValues as $paramValue ) {
			$this->assertStringContainsString( $paramValue, $text );
		}
	}

}
