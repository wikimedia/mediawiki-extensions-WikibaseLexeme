<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api\Error;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Api\Error\RepresentationsMustHaveUniqueLanguage;

/**
 * @covers \Wikibase\Lexeme\Api\Error\RepresentationsMustHaveUniqueLanguage
 *
 * @license GPL-2.0+
 */
class RepresentationsMustHaveUniqueLanguageTest extends TestCase {

	public function testApiMessageHasUnprocessableRequestCode() {
		$apiError = new RepresentationsMustHaveUniqueLanguage( 'some-param', [], 'some-language' );

		$apiMessage = $apiError->asApiMessage();

		$this->assertEquals( 'unprocessable-request', $apiMessage->getApiCode() );
	}

	public function testApiMessageHasFieldPathInData() {
		$fieldPath = [ 'a', 1, 'b' ];
		$apiError = new RepresentationsMustHaveUniqueLanguage(
			'some-param',
			$fieldPath,
			'some-language'
		);

		$apiMessage = $apiError->asApiMessage();

		assertThat(
			$apiMessage->getApiData(),
			hasKeyValuePair( 'fieldPath', equalTo( $fieldPath ) )
		);
	}

	public function testApiMessageHasDataAttributeWithParameterNameInWhichErrorOccured() {
		$apiError = new RepresentationsMustHaveUniqueLanguage( 'some-param', [], 'some-language' );

		$apiMessage = $apiError->asApiMessage();

		assertThat(
			$apiMessage->getApiData(),
			hasKeyValuePair( 'parameterName', equalTo( 'some-param' ) )
		);
	}

}
