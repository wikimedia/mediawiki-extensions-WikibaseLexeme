<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LexicalCategoryChangeOpDeserializer;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LexicalCategoryChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class LexicalCategoryChangeOpDeserializerTest extends TestCase {

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
	public function testGivenLexicalCategorySerializationIsNotString_exceptionIsThrown(
		$serialization
	) {
		$deserializer = $this->newLexicalCategoryChangeOpDeserializer();

		$this->expectException( ChangeOpDeserializationException::class );

		$deserializer->createEntityChangeOp( [ 'lexicalCategory' => $serialization ] );
	}

	private function newLexicalCategoryChangeOpDeserializer() {
		return new LexicalCategoryChangeOpDeserializer(
			$this->createMock( ValueValidator::class ),
			new StringNormalizer()
		);
	}

	public function testGivenLexicalCategorySerializationIsInvalid_exceptionIsThrown() {
		$deserializer = new LexicalCategoryChangeOpDeserializer(
			$this->createMock( ValueValidator::class ),
			new StringNormalizer()
		);

		$this->expectException( ChangeOpDeserializationException::class );

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
		$lexeme = new Lexeme( new LexemeId( 'L100' ), null, new ItemId( 'Q100' ) );

		$deserializer = $this->newLexicalCategoryChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lexicalCategory' => 'q200' ]
		);

		$changeOp->apply( $lexeme );
		$this->assertSame( 'Q200', $lexeme->getLexicalCategory()->getSerialization() );
	}

}
