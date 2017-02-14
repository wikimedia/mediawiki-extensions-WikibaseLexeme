<?php


namespace Wikibase\Lexeme\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\ChangeOp\Deserialization\LemmaChangeOpDeserializer;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Lexeme\ChangeOp\Deserialiazation\LemmaChangeOpDeserializer
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class LemmaChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	private function newLemmaChangeOpDeserializer() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return new LemmaChangeOpDeserializer(
			new TermChangeOpSerializationValidator( new StaticContentLanguages( [ 'en' ] ) ),
			new LexemeValidatorFactory( 10, $mockProvider->getMockTermValidatorFactory() ),
			new StringNormalizer()
		);
	}

	public function testGivenLemmaSerializationIsNotArray_exceptionIsThrown() {
		$deserializer = $this->newLemmaChangeOpDeserializer();

		$this->setExpectedException( ChangeOpDeserializationException::class );

		$deserializer->createEntityChangeOp( [ 'lemmas' => 'foo' ] );
	}

	public function testGivenTermChangeOpSerializationFormatInvalid_exceptionIsThrown() {
		$termChangeOpSerializationValidator = $this->getMockBuilder(
			TermChangeOpSerializationValidator::class
		)
			->disableOriginalConstructor()
			->getMock();
		$termChangeOpSerializationValidator->expects( $this->atLeastOnce() )
			->method( 'validateTermSerialization' )
			->will(
				$this->throwException( new ChangeOpDeserializationException( 'Invalid serialization', 'test' ) )
			);

		$lexemeValidatorFactory = $this->getMockBuilder( LexemeValidatorFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$deserializer = new LemmaChangeOpDeserializer(
			$termChangeOpSerializationValidator,
			$lexemeValidatorFactory,
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

	public function testGivenEmptyTermAndLemmaInLanguageExists_changeOpRemovesLemmaInTheLanguage() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ), new TermList( [ new Term( 'en', 'rat' ) ] ) );

		$deserializer = $this->newLemmaChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => '' ] ] ]
		);

		$changeOp->apply( $lexeme );
		$this->assertFalse( $lexeme->getLemmas()->hasTermForLanguage( 'en' ) );
	}

}
