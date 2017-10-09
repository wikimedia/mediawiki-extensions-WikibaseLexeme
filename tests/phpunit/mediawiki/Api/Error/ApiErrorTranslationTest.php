<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api\Error;

use Wikibase\Lexeme\Api\Error\ApiError;
use Wikibase\Lexeme\Api\Error\FormMustHaveAtLeastOneRepresentation;
use Wikibase\Lexeme\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\Api\Error\JsonFieldIsNotAnItemId;
use Wikibase\Lexeme\Api\Error\JsonFieldIsRequired;
use Wikibase\Lexeme\Api\Error\LexemeNotFound;
use Wikibase\Lexeme\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\Api\Error\ParameterIsNotLexemeId;
use Wikibase\Lexeme\Api\Error\ParameterIsRequired;
use Wikibase\Lexeme\Api\Error\RepresentationLanguageCanNotBeEmpty;
use Wikibase\Lexeme\Api\Error\RepresentationsMustHaveUniqueLanguage;
use Wikibase\Lexeme\Api\Error\RepresentationTextCanNotBeEmpty;
use Wikibase\Lexeme\DataModel\LexemeId;

class ApiErrorTranslationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideApiErrors
	 */
	public function testApiErrorsAreTranslated( ApiError $error, array $paramValues ) {
		$apiMessage = $error->asApiMessage();

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
				new JsonFieldIsRequired( 'param-1', [ 'a', 1, 'b' ] ),
				[ 'param-1', 'a/1/b' ]
			],
			JsonFieldIsNotAnItemId::class => [
				new JsonFieldIsNotAnItemId( 'param-1', [ 'a', 1, 'b' ], 'foo' ),
				[ 'param-1', 'a/1/b', 'foo' ]
			],
			JsonFieldHasWrongType::class => [
				new JsonFieldHasWrongType( 'param-1', [ 'a', 1, 'b' ], 'string', 'array' ),
				[ 'param-1', 'a/1/b', 'string', 'array' ]
			],
			ParameterIsNotLexemeId::class => [
				new ParameterIsNotLexemeId( 'param-1', 'foo' ),
				[ 'param-1', 'foo' ]
			],
			ParameterIsRequired::class => [
				new ParameterIsRequired( 'param-1' ),
				[ 'param-1' ]
			],
			RepresentationTextCanNotBeEmpty::class => [
				new RepresentationTextCanNotBeEmpty( 'param-1', [ 'a', 1, 'b' ] ),
				[]
			],
			RepresentationLanguageCanNotBeEmpty::class => [
				new RepresentationLanguageCanNotBeEmpty( 'param-1', [ 'a', 1, 'b' ] ),
				[]
			],
			FormMustHaveAtLeastOneRepresentation::class => [
				new FormMustHaveAtLeastOneRepresentation( 'param-1', [ 'a', 1, 'b' ] ),
				[]
			],
			RepresentationsMustHaveUniqueLanguage::class => [
				new RepresentationsMustHaveUniqueLanguage(
					'param-1',
					[ 'representations', 1 ],
					'dummy-language'
				),
				[ 'dummy-language' ]
			],
			LexemeNotFound::class => [
				new LexemeNotFound( new LexemeId( 'L1' ) ),
				[ 'L1' ]
			]
		];
	}

	private function assertEnglishTranslationContainsAllTheParameters(
		\Message $apiMessage,
		array $paramValues
	) {
		$text = $apiMessage->inLanguage( 'en' )->text();
		foreach ( $paramValues as $paramValue ) {
			$this->assertContains( $paramValue, $text );
		}
	}

}
