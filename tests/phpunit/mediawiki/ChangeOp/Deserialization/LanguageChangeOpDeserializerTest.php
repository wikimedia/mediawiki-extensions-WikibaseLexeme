<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\ChangeOp\Deserialization\LanguageChangeOpDeserializer;
use Wikibase\Lexeme\Domain\DataModel\Lexeme;
use Wikibase\Lexeme\Domain\DataModel\LexemeId;
use Wikibase\Lexeme\Tests\MediaWiki\Validators\LexemeValidatorFactoryTestMockProvider;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\StringNormalizer;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\Deserialization\LanguageChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class LanguageChangeOpDeserializerTest extends TestCase {

	use PHPUnit4And6Compat;

	private function newLanguageChangeOpDeserializer() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		$validatorFactoryMockProvider = new LexemeValidatorFactoryTestMockProvider();
		return new LanguageChangeOpDeserializer(
			$validatorFactoryMockProvider->getLexemeValidatorFactory(
				$this,
				10,
				$mockProvider->getMockTermValidatorFactory()
			),
			new StringNormalizer()
		);
	}

	public function provideInvalidSerialization() {
		return [
			[ [] ],
			[ null ],
			[ '' ],
		];
	}

	/**
	 * @dataProvider provideInvalidSerialization
	 */
	public function testGivenLanguageSerializationIsNotString_exceptionIsThrown(
		$serialization
	) {
		$deserializer = $this->newLanguageChangeOpDeserializer();

		$this->setExpectedException( ChangeOpDeserializationException::class );

		$deserializer->createEntityChangeOp( [ 'language' => $serialization ] );
	}

	public function testGivenLanguageSerializationIsInvalid_exceptionIsThrown() {
		$lexemeValidatorFactory = $this->getMockBuilder( LexemeValidatorFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$deserializer = new LanguageChangeOpDeserializer(
			$lexemeValidatorFactory,
			new StringNormalizer()
		);

		$this->setExpectedException( ChangeOpDeserializationException::class );

		// Invalid ItemId (not a Q###)
		$deserializer->createEntityChangeOp( [ 'language' => 'invalid item id' ] );
	}

	public function testGivenRequestWithLanguageAndNoLanguage_changeOpAddsTheLanguage() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ) );

		$deserializer = $this->newLanguageChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'language' => 'q100' ]
		);

		$changeOp->apply( $lexeme );
		$this->assertSame( 'Q100', $lexeme->getLanguage()->getSerialization() );
	}

	public function testGivenRequestWithLanguageExists_changeOpSetsLanguageToNewValue() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ), null, null, new ItemId( 'Q100' ) );

		$deserializer = $this->newLanguageChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'language' => 'q200' ]
		);

		$changeOp->apply( $lexeme );
		$this->assertSame( 'Q200', $lexeme->getLanguage()->getSerialization() );
	}

}
