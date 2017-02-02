<?php

namespace Wikibase\Lexeme\Tests\Validators;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @covers Wikibase\Lexeme\Validators\LexemeValidatorFactory
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class LexemeValidatorFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invalidConstructorArgsProvider
	 */
	public function testGivenInvalidMaxLength_constructorThrowsException( $maxLength ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new LexemeValidatorFactory( $maxLength, $this->getTermValidatorFactory() );
	}

	public function invalidConstructorArgsProvider() {
		return [
			[ 'foo' ],
			[ false ],
			[ null ],
		];
	}

	public function testGetLanguageCodeValidator() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		$dupeDetector = $mockProvider->getMockLabelDescriptionDuplicateDetector();
		$termValidatorFactory = new TermValidatorFactory(
			100,
			[ 'en', 'fr', 'de' ],
			new BasicEntityIdParser(),
			$dupeDetector
		);

		$languageCodeValidator = ( new LexemeValidatorFactory(
			100,
			$termValidatorFactory
		) )->getLanguageCodeValidator();

		$this->assertTrue( $languageCodeValidator->validate( 'en' )->isValid() );
		$this->assertTrue( $languageCodeValidator->validate( 'fr' )->isValid() );
		$this->assertFalse( $languageCodeValidator->validate( 'xx' )->isValid() );
	}

	/**
	 * @dataProvider lemmaTermProvider
	 */
	public function testGetLemmaTermValidator( $isValid, $lemmaTerm ) {
		$lemmaValidator = ( new LexemeValidatorFactory(
			10,
			$this->getTermValidatorFactory() )
		)->getLemmaTermValidator();

		$this->assertSame(
			$isValid,
			$lemmaValidator->validate( $lemmaTerm )->isValid()
		);
	}

	public function lemmaTermProvider() {
		return [
			'valid' => [ true, 'foo' ],
			'not a string' => [ false, false ],
			'empty' => [ false, '' ],
			'exceeds maxLength of 10' => [ false, 'foooooooooo' ],
			'leading whitespace' => [ false, ' foo' ],
			'trailing whitespace' => [ false, 'foo ' ],
		];
	}

	private function getTermValidatorFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockTermValidatorFactory();
	}

}
