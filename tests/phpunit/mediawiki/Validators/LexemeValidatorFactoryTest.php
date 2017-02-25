<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Validators;

use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\Repo\Validators\EntityExistsValidator;
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
		$this->getLexemeValidatorFactory( $maxLength );
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

		$languageCodeValidator = $this
			->getLexemeValidatorFactory( 100, $termValidatorFactory )
			->getLanguageCodeValidator();

		$this->assertTrue( $languageCodeValidator->validate( 'en' )->isValid() );
		$this->assertTrue( $languageCodeValidator->validate( 'fr' )->isValid() );
		$this->assertFalse( $languageCodeValidator->validate( 'xx' )->isValid() );
	}

	/**
	 * @dataProvider lemmaTermProvider
	 */
	public function testGetLemmaTermValidator( $isValid, $lemmaTerm ) {
		$lemmaValidator = $this
			->getLexemeValidatorFactory( 10 )
			->getLemmaTermValidator();

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

	public function testGetLanguageValidatorValid() {
		$languageValidator = $this
			->getLexemeValidatorFactory( 10, null, [ 'Q123' ] )
			->getLanguageValidator();

		$this->assertTrue(
			$languageValidator->validate( new ItemId( 'Q123' ) )->isValid()
		);
	}

	public function testGetLanguageValidatorEmptyLanguageValid() {
		$languageValidator = $this
			->getLexemeValidatorFactory( 10, null, [ 'Q123' ] )
			->getLanguageValidator();

		$this->assertTrue(
			$languageValidator->validate( null )->isValid()
		);
	}

	/**
	 * @dataProvider languageProviderInvalid
	 */
	public function testGetLanguageValidatorInvalid( $language ) {
		$languageValidator = $this
			->getLexemeValidatorFactory( 10, null, [ 'Q123' ] )
			->getLanguageValidator();

		$this->assertFalse(
			$languageValidator->validate( $language )->isValid()
		);
	}

	public function languageProviderInvalid() {
		return [
			'not existing item' => [ new ItemId( 'Q321' ) ],
			'property' => [ new PropertyId( 'P321' ) ],
			'lexeme' => [ new LexemeId( 'L321' ) ],
		];
	}

	/**
	 * @dataProvider invalidLanguageValidatorArgsProvider
	 */
	public function testGiveninputIsInvalid_validatorThrowsException( $language ) {
		$this->setExpectedException( InvalidArgumentException::class );
		$languageValidator = $this
			->getLexemeValidatorFactory( 10, null, [ 'Q123' ] )
			->getLanguageValidator();
		$languageValidator->validate( $language )->isValid();
	}

	public function invalidLanguageValidatorArgsProvider() {
		return [
			[ false ],
			[ '' ],
			[ 'Q123' ]
		];
	}

	/**
	 * @param int $maxLength
	 * @param TermValidatorFactory|null $termValidatorFactory
	 * @param string[] $existingItemIds
	 * @return LexemeValidatorFactory
	 */
	private function getLexemeValidatorFactory(
		$maxLength,
		TermValidatorFactory $termValidatorFactory = null,
		array $existingItemIds = []
	) {
		return new LexemeValidatorFactory(
			$maxLength,
			$termValidatorFactory === null ? $this->getTermValidatorFactory() :
				$termValidatorFactory,
			$this->getItemValidator( $existingItemIds )
		);
	}

	/**
	 * @return TermValidatorFactory
	 */
	private function getTermValidatorFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockTermValidatorFactory();
	}

	/**
	 * @param string[] $existingItemIds
	 * @return ValueValidator[]
	 */
	private function getItemValidator(
		array $existingItemIds = []
	) {
		$validatorMock = $this->getMockBuilder( EntityExistsValidator::class )
			->disableOriginalConstructor()
			->getMock();
		$validatorMock->expects( $this->any() )
			->method( 'validate' )
			->will( $this->returnCallback( function ( $itemId ) use ( $existingItemIds ) {
				return $this->validateItemId( $itemId, $existingItemIds );
			} ) );
		return [ $validatorMock ];
	}

	/**
	 * @param mixed $itemId
	 * @param string[] $existingItemIds
	 * @return Error|Result
	 */
	private function validateItemId( $itemId, array $existingItemIds = [] ) {
		if ( $itemId === null ) {
			return Result::newSuccess();
		}

		if ( !$itemId instanceof EntityId ) {
			throw new InvalidArgumentException( "Expected an EntityId object" );
		}

		if ( !$itemId instanceof ItemId ) {
			$error = Error::newError(
				"Wrong entity type: " . $itemId->getEntityType(),
				null,
				'bad-entity-type',
				[ $itemId->getEntityType() ]
			);
			return Result::newError( [ $error ] );
		}

		if ( in_array( $itemId->getSerialization(), $existingItemIds ) ) {
			return Result::newSuccess();
		}

		$error = Error::newError(
			"Entity not found: " . $itemId->getSerialization(),
			null, 'no-such-entity',
			[ $itemId->getSerialization() ]
		);
		return Result::newError( [ $error ] );
	}

}
