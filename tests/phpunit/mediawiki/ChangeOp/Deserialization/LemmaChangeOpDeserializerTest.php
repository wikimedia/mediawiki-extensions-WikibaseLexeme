<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\ChangeOp\Deserialization\LemmaChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\StringNormalizer;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\Deserialization\LemmaChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class LemmaChangeOpDeserializerTest extends TestCase {

	use PHPUnit4And6Compat;

	private function newLemmaChangeOpDeserializer() {
		$lemmaTermValidator = $this->getMockBuilder( CompositeValidator::class )
			->disableOriginalConstructor()
			->getMock();

		return new LemmaChangeOpDeserializer(
			new LexemeTermSerializationValidator(
				new LexemeTermLanguageValidator( new StaticContentLanguages( [ 'en' ] ) )
			),
			$lemmaTermValidator,
			new StringNormalizer()
		);
	}

	public function testGivenLemmaSerializationIsNotArray_exceptionIsThrown() {
		$deserializer = $this->newLemmaChangeOpDeserializer();

		$this->setExpectedException( ChangeOpDeserializationException::class );

		$deserializer->createEntityChangeOp( [ 'lemmas' => 'foo' ] );
	}

	public function testGivenTermChangeOpSerializationFormatInvalid_exceptionIsThrown() {
		$termSerializationValidator = $this->getMockBuilder(
			LexemeTermSerializationValidator::class
		)
			->disableOriginalConstructor()
			->getMock();
		$termSerializationValidator->expects( $this->atLeastOnce() )
			->method( 'validate' )
			->will(
				$this->throwException( new ChangeOpDeserializationException( 'Invalid serialization', 'test' ) )
			);

		$lemmaTermValidator = $this->getMockBuilder( CompositeValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$deserializer = new LemmaChangeOpDeserializer(
			$termSerializationValidator,
			$lemmaTermValidator,
			new StringNormalizer()
		);

		$this->setExpectedException( ChangeOpDeserializationException::class );

		$deserializer->createEntityChangeOp( [ 'lemmas' => [ 'invalid term change serialization' ] ] );
	}

	// TODO: should case of ChangeOp with invalid Term (ie. throwing ChangeOpException
	// when applied on Lexeme object) be also tested here?

	public function testGivenRequestWithLemmaTermAndNoLemmaInLanguage_changeOpAddsTheLemma() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ), new TermList( [] ) );

		$deserializer = $this->newLemmaChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'rat' ] ] ]
		);

		$changeOp->apply( $lexeme );
		$this->assertSame( 'rat', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenRequestWithTermAndLemmaInLanguageExists_changeOpSetsLemmaToNewValue() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ), new TermList( [ new Term( 'en', 'cat' ) ] ) );

		$deserializer = $this->newLemmaChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'rat' ] ] ]
		);

		$changeOp->apply( $lexeme );
		$this->assertSame( 'rat', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenRemoveRequestLemmaInLanguageExists_changeOpRemovesLemmaInTheLanguage() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ), new TermList( [ new Term( 'en', 'rat' ) ] ) );

		$deserializer = $this->newLemmaChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ]
		);

		$changeOp->apply( $lexeme );
		$this->assertFalse( $lexeme->getLemmas()->hasTermForLanguage( 'en' ) );
	}

	public function testGivenEmptyTerm_exceptionIsThrown() {
		$deserializer = $this->newLemmaChangeOpDeserializer();

		try {
			$deserializer->createEntityChangeOp(
				[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => '' ] ] ]
			);
		} catch ( \ApiUsageException $ex ) {
			$exception = $ex;
		}

		$message = $exception->getMessageObject();
		$this->assertEquals( 'unprocessable-request', $message->getApiCode() );
		$this->assertEquals(
			'wikibaselexeme-api-error-lexeme-term-text-cannot-be-empty',
			$message->getKey()
		);
		$this->assertEquals(
			[ 'parameterName' => 'lemmas', 'fieldPath' => [ 'en' ] ],
			$message->getApiData()
		);
	}

}
