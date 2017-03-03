<?php

namespace Wikibase\Lexeme\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\ChangeOp\Deserialization\LanguageChangeOpDeserializer;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Tests\MediaWiki\Validators\LexemeValidatorFactoryTestMockProvider;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Lexeme\ChangeOp\Deserialiazation\LanguageChangeOpDeserializer
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class LanguageChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

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

	public function testGivenLanguageSerializationIsNotString_exceptionIsThrown() {
		$deserializer = $this->newLanguageChangeOpDeserializer();

		$this->setExpectedException( ChangeOpDeserializationException::class );

		$deserializer->createEntityChangeOp( [ 'language' => [] ] );
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

	public function testGivenRemoveRequestLanguageExists_changeOpRemovesLanguage() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ), null, null, new ItemId( 'Q100' ) );

		$deserializer = $this->newLanguageChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'language' => '' ]
		);

		$changeOp->apply( $lexeme );
		$this->assertNull( $lexeme->getLanguage() );
	}

	public function testRequestRemoveLanguageDoesNotExist_changeOpDoesNothing() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ) );

		$deserializer = $this->newLanguageChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'language' => '' ]
		);

		$changeOp->apply( $lexeme );
		$this->assertNull( $lexeme->getLanguage() );
	}

}
