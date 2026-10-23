<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use MediaWiki\Api\ApiUsageException;
use MediaWiki\Api\IApiMessage;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLemmaEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLemmaRemove;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermValueValidator;
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
class LemmaChangeOpDeserializerTest extends \MediaWikiIntegrationTestCase {

	private function newLemmaChangeOpDeserializer() {
		return new LemmaChangeOpDeserializer(
			new LexemeTermSerializationValidator(
				new LexemeTermLanguageValidator( new StaticContentLanguages( [ 'en' ] ) ),
				new LexemeTermValueValidator( LexemeTermValueValidator::MAX_LENGTH ),
			),
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
			->willThrowException( new ChangeOpDeserializationException( 'Invalid serialization', 'test' ) );

		$deserializer = new LemmaChangeOpDeserializer(
			$termSerializationValidator,
			new StringNormalizer()
		);

		$this->expectException( ChangeOpDeserializationException::class );

		$deserializer->createEntityChangeOp( [ 'lemmas' => [ 'invalid term change serialization' ] ] );
	}

	public function testGivenTooLongLemma_exceptionIsThrown() {
		$deserializer = $this->newLemmaChangeOpDeserializer();

		$this->expectException( ApiUsageException::class );

		$deserializer->createEntityChangeOp( [ 'lemmas' => [ 'en' => [
			'language' => 'en',
			'value' => str_repeat( 'x', LexemeTermValueValidator::MAX_LENGTH + 1 ),
		] ] ] );
	}

	public function testGivenLemmaWithSurroundingWhitespace_validatesTrimmedLemma() {
		$termSerializationValidator = $this->createMock( LexemeTermSerializationValidator::class );
		$termSerializationValidator->expects( $this->once() )
			->method( 'validateText' )
			->with( 'rat' );

		$deserializer = new LemmaChangeOpDeserializer(
			$termSerializationValidator,
			new StringNormalizer()
		);

		$deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => " \t rat \n " ] ] ]
		);
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
		} catch ( ApiUsageException $ex ) {
			$exception = $ex;
		}

		$status = $exception->getStatusValue();
		/** @var IApiMessage $message */
		$message = $status->getMessages()[0];
		$this->assertEquals( 'not-recognized-language', $message->getApiCode() );
		$this->assertStatusError( 'apierror-wikibaselexeme-unknown-language-withtext', $status );
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
		} catch ( ApiUsageException $ex ) {
			$exception = $ex;
		}

		$status = $exception->getStatusValue();
		/** @var IApiMessage $message */
		$message = $status->getMessages()[0];
		$this->assertEquals( 'unprocessable-request', $message->getApiCode() );
		$this->assertStatusError(
			'apierror-wikibaselexeme-lexeme-term-text-cannot-be-empty',
			$status
		);
		$this->assertEquals(
			[ 'parameterName' => 'lemmas', 'fieldPath' => [ 'en' ] ],
			$message->getApiData()
		);
	}

}
