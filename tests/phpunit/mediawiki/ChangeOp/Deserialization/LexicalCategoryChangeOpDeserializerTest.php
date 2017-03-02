<?php

namespace Wikibase\Lexeme\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\ChangeOp\Deserialization\LexicalCategoryChangeOpDeserializer;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Tests\MediaWiki\Validators\LexemeValidatorFactoryTestMockProvider;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Lexeme\ChangeOp\Deserialiazation\LexicalCategoryChangeOpDeserializer
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class LexicalCategoryChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	private function newLexicalCategoryChangeOpDeserializer() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		$validatorFactoryMockProvider = new LexemeValidatorFactoryTestMockProvider();
		return new LexicalCategoryChangeOpDeserializer(
			$validatorFactoryMockProvider->getLexemeValidatorFactory(
				$this,
				10,
				$mockProvider->getMockTermValidatorFactory()
			),
			new StringNormalizer()
		);
	}

	public function testGivenLexicalCategorySerializationIsNotString_exceptionIsThrown() {
		$deserializer = $this->newLexicalCategoryChangeOpDeserializer();

		$this->setExpectedException( ChangeOpDeserializationException::class );

		$deserializer->createEntityChangeOp( [ 'lexicalCategory' => [] ] );
	}

	public function testGivenLexicalCategorySerializationIsInvalid_exceptionIsThrown() {
		$lexemeValidatorFactory = $this->getMockBuilder( LexemeValidatorFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$deserializer = new LexicalCategoryChangeOpDeserializer(
			$lexemeValidatorFactory,
			new StringNormalizer()
		);

		$this->setExpectedException( ChangeOpDeserializationException::class );

		// Invalid ItemId (not a Q###)
		$deserializer->createEntityChangeOp( [ 'lexicalCategory' => 'invalid item id' ] );
	}

	public function testGivenRequestLexicalCategoryAndNoLexicalCategory_changeOpAddsLexicalCategory() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ) );

		$deserializer = $this->newLexicalCategoryChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lexicalCategory' => 'q100' ]
		);

		$changeOp->apply( $lexeme );
		$this->assertSame( 'Q100', $lexeme->getLexicalCategory()->getSerialization() );
	}

	public function testGivenRequestWithLexicalCategoryExists_changeOpSetsLexicalCategoryToNewValue() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ), null, null, new ItemId( 'Q100' ) );

		$deserializer = $this->newLexicalCategoryChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lexicalCategory' => 'q200' ]
		);

		$changeOp->apply( $lexeme );
		$this->assertSame( 'Q200', $lexeme->getLexicalCategory()->getSerialization() );
	}

	public function testGivenRemoveRequestLexicalCategoryExists_changeOpRemovesLexicalCategory() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ), null, null, new ItemId( 'Q100' ) );

		$deserializer = $this->newLexicalCategoryChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lexicalCategory' => '' ]
		);

		$changeOp->apply( $lexeme );
		$this->assertNull( $lexeme->getLexicalCategory() );
	}

	public function testRequestRemoveLexicalCategoryDoesNotExist_changeOpDoesNothing() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ) );

		$deserializer = $this->newLexicalCategoryChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lexicalCategory' => '' ]
		);

		$changeOp->apply( $lexeme );
		$this->assertNull( $lexeme->getLexicalCategory() );
	}

}
