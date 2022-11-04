<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLemmaEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLemmaRemove;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LemmaChangeOpDeserializer;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LemmaChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class LemmaChangeOpDeserializerTest extends TestCase {

	private function newLemmaChangeOpDeserializer() {
		$lemmaTermValidator = $this->createMock( LemmaTermValidator::class );

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

		$this->expectException( ChangeOpDeserializationException::class );

		$deserializer->createEntityChangeOp( [ 'lemmas' => 'foo' ] );
	}

	public function testGivenTermChangeOpSerializationFormatInvalid_exceptionIsThrown() {
		$termSerializationValidator = $this->createMock( LexemeTermSerializationValidator::class );
		$termSerializationValidator->expects( $this->atLeastOnce() )
			->method( 'validateStructure' )
			->will(
				$this->throwException( new ChangeOpDeserializationException( 'Invalid serialization', 'test' ) )
			);

		$lemmaTermValidator = $this->createMock( LemmaTermValidator::class );

		$deserializer = new LemmaChangeOpDeserializer(
			$termSerializationValidator,
			$lemmaTermValidator,
			new StringNormalizer()
		);

		$this->expectException( ChangeOpDeserializationException::class );

		$deserializer->createEntityChangeOp( [ 'lemmas' => [ 'invalid term change serialization' ] ] );
	}

	// TODO: should case of ChangeOp with invalid Term (ie. throwing ChangeOpException
	// when applied on Lexeme object) be also tested here?

	public function testGivenRequestWithLemmaTermAndNoLemmaInLanguage_changeOpAddsTheLemma() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ), new TermList( [] ) );

		$deserializer = $this->newLemmaChangeOpDeserializer();

		/**
		 * @var ChangeOps $changeOps
		 */
		$changeOps = $deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'rat' ] ] ]
		);

		$changeOps->apply( $lexeme );

		$this->assertInstanceOf( ChangeOpLemmaEdit::class, $changeOps->getChangeOps()[0] );
		$this->assertSame( 'rat', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenRequestWithTermAndLemmaInLanguageExists_changeOpSetsLemmaToNewValue() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ), new TermList( [ new Term( 'en', 'cat' ) ] ) );

		$deserializer = $this->newLemmaChangeOpDeserializer();

		/**
		 * @var ChangeOps $changeOps
		 */
		$changeOps = $deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'rat' ] ] ]
		);

		$changeOps->apply( $lexeme );

		$this->assertInstanceOf( ChangeOpLemmaEdit::class, $changeOps->getChangeOps()[0] );
		$this->assertSame( 'rat', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenRemoveRequestLemmaInLanguageExists_changeOpRemovesLemmaInTheLanguage() {
		$lexeme = new Lexeme( new LexemeId( 'L100' ), new TermList( [ new Term( 'en', 'rat' ) ] ) );

		$deserializer = $this->newLemmaChangeOpDeserializer();

		/**
		 * @var ChangeOps $changeOps
		 */
		$changeOps = $deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ]
		);

		$changeOps->apply( $lexeme );

		$this->assertInstanceOf( ChangeOpLemmaRemove::class, $changeOps->getChangeOps()[0] );
		$this->assertFalse( $lexeme->getLemmas()->hasTermForLanguage( 'en' ) );
	}

	public function testGivenRequestWithInvalidLanguage_exceptionIsThrown(): void {
		$deserializer = $this->newLemmaChangeOpDeserializer();

		try {
			$deserializer->createEntityChangeOp(
				[ 'lemmas' => [ 'invalid' => [ 'language' => 'invalid', 'value' => 'abc' ] ] ]
			);
		} catch ( \ApiUsageException $ex ) {
			$exception = $ex;
		}

		$message = $exception->getMessageObject();
		$this->assertEquals( 'not-recognized-language', $message->getApiCode() );
		$this->assertEquals(
			'apierror-wikibaselexeme-unknown-language-withtext',
			$message->getKey()
		);
		$this->assertEquals(
			[ 'parameterName' => 'lemmas', 'fieldPath' => [ 'invalid' ] ],
			$message->getApiData()
		);
	}

	public function testGivenRemoveRequestWithInvalidLanguage_changeOpRemovesLemmaInTheLanguage(): void {
		$lexeme = new Lexeme( new LexemeId( 'L100' ), new TermList( [
			new Term( 'en', 'en lemma' ),
			new Term( 'invalid', 'invalid lemma' ),
		] ) );

		$deserializer = $this->newLemmaChangeOpDeserializer();

		$changeOps = $deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'invalid' => [ 'language' => 'invalid', 'remove' => '' ] ] ]
		);

		$changeOps->apply( $lexeme );

		$this->assertInstanceOf( ChangeOpLemmaRemove::class, $changeOps->getChangeOps()[0] );
		$this->assertFalse( $lexeme->getLemmas()->hasTermForLanguage( 'invalid' ) );
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
			'apierror-wikibaselexeme-lexeme-term-text-cannot-be-empty',
			$message->getKey()
		);
		$this->assertEquals(
			[ 'parameterName' => 'lemmas', 'fieldPath' => [ 'en' ] ],
			$message->getApiData()
		);
	}

}
