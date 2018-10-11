<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Validators;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\LexemeValidatorFactory;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\Repo\Validators\EntityExistsValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * Provider of the LexemeValidatorFactory mock for test purposes
 * @license GPL-2.0-or-later
 */
class LexemeValidatorFactoryTestMockProvider {

	/**
	 * @param TestCase $testCase
	 *
	 * @return TermValidatorFactory
	 */
	private function getTermValidatorFactory( TestCase $testCase ) {
		$mockProvider = new ChangeOpTestMockProvider( $testCase );
		return $mockProvider->getMockTermValidatorFactory();
	}

	/**
	 * @param EntityId|null $itemId
	 * @param string[] $existingItemIds
	 *
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

	/**
	 * @param TestCase $testCase
	 * @param string[] $existingItemIds
	 *
	 * @return ValueValidator[]
	 */
	private function getItemValidator(
		TestCase $testCase,
		array $existingItemIds = []
	) {
		$validatorMock = $testCase->getMockBuilder( EntityExistsValidator::class )
			->disableOriginalConstructor()
			->getMock();
		$validatorMock->method( 'validate' )
			->will( $testCase->returnCallback( function ( $itemId ) use ( $existingItemIds ) {
				return $this->validateItemId( $itemId, $existingItemIds );
			} ) );
		return [ $validatorMock ];
	}

	/**
	 * @param TestCase $testCase
	 * @param int $maxLength
	 * @param TermValidatorFactory|null $termValidatorFactory
	 * @param string[] $existingItemIds
	 *
	 * @return \Wikibase\Lexeme\LexemeValidatorFactory
	 */
	public function getLexemeValidatorFactory(
		TestCase $testCase,
		$maxLength,
		TermValidatorFactory $termValidatorFactory = null,
		array $existingItemIds = []
	) {
		return new \Wikibase\Lexeme\LexemeValidatorFactory(
			$maxLength,
			$termValidatorFactory === null ? $this->getTermValidatorFactory( $testCase ) :
				$termValidatorFactory,
			$this->getItemValidator( $testCase, $existingItemIds )
		);
	}

}
