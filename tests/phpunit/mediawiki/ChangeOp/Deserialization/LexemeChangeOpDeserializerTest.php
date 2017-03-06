<?php

namespace Wikibase\Lexeme\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\Lexeme\ChangeOp\Deserialization\LanguageChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LemmaChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LexemeChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\LexicalCategoryChangeOpDeserializer;

/**
 * @covers Wikibase\Lexeme\ChangeOp\Deserialization\LexemeChangeOpDeserializer
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class LexemeChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return LemmaChangeOpDeserializer
	 */
	private function getLemmaChangeOpDeserializer() {
		 return $this->getMockBuilder( LemmaChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return LanguageChangeOpDeserializer
	 */
	private function getLanguageChangeOpDeserializer() {
		return $this->getMockBuilder( LanguageChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return LexicalCategoryChangeOpDeserializer
	 */
	private function getLexicalCategoryChangeOpDeserializer() {
		return $this->getMockBuilder( LexicalCategoryChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCreateEntityChangeOpReturnsChangeOpInstance() {
		$deserializer = new LexemeChangeOpDeserializer(
			$this->getLemmaChangeOpDeserializer(),
			$this->getLexicalCategoryChangeOpDeserializer(),
			$this->getLanguageChangeOpDeserializer()
		);

		$this->assertInstanceOf( ChangeOp::class, $deserializer->createEntityChangeOp( [] ) );
	}

	public function testGivenLemmasInChangeRequest_lemmaChangeOpDeserializerIsUsed() {
		$lemmaDeserializer = $this->getLemmaChangeOpDeserializer();
		$lemmaDeserializer->expects( $this->atLeastOnce() )
			->method( 'createEntityChangeOp' )
			->will( $this->returnValue( $this->getMock( ChangeOp::class ) ) );

		$languageDeserializer = $this->getLanguageChangeOpDeserializer();
		$languageDeserializer->expects( $this->never() )
			->method( $this->anything() );

		$lexicalCategoryDeserializer = $this->getLexicalCategoryChangeOpDeserializer();
		$lexicalCategoryDeserializer->expects( $this->never() )
			->method( $this->anything() );

		$deserializer = new LexemeChangeOpDeserializer(
			$lemmaDeserializer,
			$lexicalCategoryDeserializer,
			$languageDeserializer
		);

		$deserializer->createEntityChangeOp(
			[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'rat' ] ] ]
		);
	}

	public function testGivenLanguageInChangeRequest_languageChangeOpDeserializerIsUsed() {
		$lemmaDeserializer = $this->getLemmaChangeOpDeserializer();
		$lemmaDeserializer->expects( $this->never() )
			->method( $this->anything() );

		$languageDeserializer = $this->getLanguageChangeOpDeserializer();
		$languageDeserializer->expects( $this->atLeastOnce() )
			->method( 'createEntityChangeOp' )
			->will( $this->returnValue( $this->getMock( ChangeOp::class ) ) );

		$lexicalCategoryDeserializer = $this->getLexicalCategoryChangeOpDeserializer();
		$lexicalCategoryDeserializer->expects( $this->never() )
			->method( $this->anything() );

		$deserializer = new LexemeChangeOpDeserializer(
			$lemmaDeserializer,
			$lexicalCategoryDeserializer,
			$languageDeserializer
		);

		$deserializer->createEntityChangeOp(
			[ 'language' => 'q100' ]
		);
	}

	public function testGivenLexCatInChangeRequest_lexCatChangeOpDeserializerIsUsed() {
		$lemmaDeserializer = $this->getLemmaChangeOpDeserializer();
		$lemmaDeserializer->expects( $this->never() )
			->method( $this->anything() );

		$languageDeserializer = $this->getLanguageChangeOpDeserializer();
		$languageDeserializer->expects( $this->never() )
			->method( $this->anything() );

		$lexicalCategoryDeserializer = $this->getLexicalCategoryChangeOpDeserializer();
		$lexicalCategoryDeserializer->expects( $this->atLeastOnce() )
			->method( 'createEntityChangeOp' )
			->will( $this->returnValue( $this->getMock( ChangeOp::class ) ) );

		$deserializer = new LexemeChangeOpDeserializer(
			$lemmaDeserializer,
			$lexicalCategoryDeserializer,
			$languageDeserializer
		);

		$deserializer->createEntityChangeOp(
			[ 'lexicalCategory' => 'q200' ]
		);
	}

	public function testGivenNoLexemeRelevantFieldsInRequest_lemmaChangeOpDeserializerIsNotUsed() {
		$lemmaDeserializer = $this->getLemmaChangeOpDeserializer();
		$lemmaDeserializer->expects( $this->never() )
			->method( $this->anything() );

		$languageDeserializer = $this->getLanguageChangeOpDeserializer();
		$languageDeserializer->expects( $this->never() )
			->method( $this->anything() );

		$lexicalCategoryDeserializer = $this->getLexicalCategoryChangeOpDeserializer();
		$lexicalCategoryDeserializer->expects( $this->never() )
			->method( $this->anything() );

		$deserializer = new LexemeChangeOpDeserializer(
			$lemmaDeserializer,
			$lexicalCategoryDeserializer,
			$languageDeserializer
		);

		$deserializer->createEntityChangeOp(
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'rat' ] ] ]
		);
	}

}
