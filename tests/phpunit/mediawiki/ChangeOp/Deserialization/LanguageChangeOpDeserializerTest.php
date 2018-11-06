<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LanguageChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\StringNormalizer;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LanguageChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class LanguageChangeOpDeserializerTest extends TestCase {

	use PHPUnit4And6Compat;

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

	private function newLanguageChangeOpDeserializer() {
		return new LanguageChangeOpDeserializer(
			$this->createMock( ValueValidator::class ),
			new StringNormalizer()
		);
	}

	public function testGivenLanguageSerializationIsInvalid_exceptionIsThrown() {
		$deserializer = new LanguageChangeOpDeserializer(
			$this->createMock( ValueValidator::class ),
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
