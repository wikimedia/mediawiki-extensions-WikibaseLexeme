<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use PHPUnit4And6Compat;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\Api\Error\JsonFieldIsRequired;
use Wikibase\Lexeme\Api\Error\RepresentationLanguageCanNotBeEmpty;
use Wikibase\Lexeme\Api\Error\RepresentationLanguageInconsistent;
use Wikibase\Lexeme\ChangeOp\ChangeOpRemoveFormRepresentation;
use Wikibase\Lexeme\ChangeOp\ChangeOpRepresentation;
use Wikibase\Lexeme\ChangeOp\ChangeOpRepresentationList;
use Wikibase\Lexeme\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ValidationContext;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class RepresentationsChangeOpDeserializerTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testCreateEntityChangeOpEmpty_yieldsZeroChangeOpRepresentationList() {
		$representationDeserializer = $this->getMock( TermDeserializer::class );
		$deserializer = new RepresentationsChangeOpDeserializer( $representationDeserializer );
		$changeOps = $deserializer->createEntityChangeOp( [] );

		$this->assertInstanceOf( ChangeOpRepresentationList::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testEmptyLanguageKey_addsViolation() {
		$representationDeserializer = $this->getMock( TermDeserializer::class );
		$deserializer = new RepresentationsChangeOpDeserializer( $representationDeserializer );
		$representationContext = $this->getContextSpy();
		$representationContext
			->expects( $this->once() )
			->method( 'addViolation' )
			->with( new RepresentationLanguageCanNotBeEmpty() );
		$deserializer->setContext( $representationContext );

		$changeOps = $deserializer->createEntityChangeOp( [ '' => [ 'empty key is bad' ] ] );

		$this->assertInstanceOf( ChangeOpRepresentationList::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testMissingLanguageProperty_addsViolation() {
		$representationDeserializer = $this->getMock( TermDeserializer::class );
		$deserializer = new RepresentationsChangeOpDeserializer( $representationDeserializer );
		$representationContext = $this->getContextSpy();
		$languageContext = $this->getContextSpy();
		$languageContext
			->expects( $this->once() )
			->method( 'addViolation' )
			->with( new JsonFieldIsRequired( 'language' ) );
		$representationContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $representationContext );

		$changeOps = $deserializer->createEntityChangeOp( [ 'en' => [ 'value' => 'smth' ] ] );

		$this->assertInstanceOf( ChangeOpRepresentationList::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testImbalancedLanguages_addsViolation() {
		$representationDeserializer = $this->getMock( TermDeserializer::class );
		$deserializer = new RepresentationsChangeOpDeserializer( $representationDeserializer );
		$representationContext = $this->getContextSpy();
		$languageContext = $this->getContextSpy();
		$languageContext
			->expects( $this->once() )
			->method( 'addViolation' )
			->with( new RepresentationLanguageInconsistent( 'en', 'de' ) );
		$representationContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $representationContext );

		$changeOps = $deserializer->createEntityChangeOp( [
			'en' => [ 'language' => 'de', 'value' => 'smth' ]
		] );

		$this->assertInstanceOf( ChangeOpRepresentationList::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithValidTerm_yieldsChangeOpRepresentationList() {
		$representationDeserializer = $this->getMock( TermDeserializer::class );
		$representationDeserializer
			->expects( $this->once() )
			->method( 'deserialize' )
			->with( [ 'language' => 'en', 'value' => 'smth' ] )
			->willReturn( new Term( 'en', 'smth' ) );

		$deserializer = new RepresentationsChangeOpDeserializer( $representationDeserializer );

		$languageContext = $this->getContextSpy();
		$representationContext = $this->getContextSpy();
		$representationContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $representationContext );

		$changeOps = $deserializer->createEntityChangeOp( [
			'en' => [ 'language' => 'en', 'value' => 'smth' ]
		] );

		$this->assertInstanceOf( ChangeOpRepresentationList::class, $changeOps );
		$this->assertCount( 1, $changeOps->getChangeOps() );
		$this->assertInstanceOf( ChangeOpRepresentation::class, $changeOps->getChangeOps()[0] );
	}

	public function testCreateEntityChangeOpWithRemoval_yieldsChangeOpRepresentationList() {
		$representationDeserializer = $this->getMock( TermDeserializer::class );
		$deserializer = new RepresentationsChangeOpDeserializer( $representationDeserializer );

		$languageContext = $this->getContextSpy();
		$representationContext = $this->getContextSpy();
		$representationContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $representationContext );

		$changeOps = $deserializer->createEntityChangeOp( [
			'en' => [ 'language' => 'en', 'remove' => '' ]
		] );

		$this->assertInstanceOf( ChangeOpRepresentationList::class, $changeOps );
		$this->assertCount( 1, $changeOps->getChangeOps() );
		$this->assertInstanceOf( ChangeOpRemoveFormRepresentation::class, $changeOps->getChangeOps()[0] );
	}

	public function testCreateEntityChangeOpWithMissingTermDeserializeException_addsViolation() {
		$representationDeserializer = $this->getMock( TermDeserializer::class );
		$representationDeserializer
			->expects( $this->once() )
			->method( 'deserialize' )
			->willThrowException( new MissingAttributeException( 'value', 'so sad' ) );

		$deserializer = new RepresentationsChangeOpDeserializer( $representationDeserializer );

		$languageContext = $this->getContextSpy();
		$languageContext
			->expects( $this->once() )
			->method( 'addViolation' )
			->with( new JsonFieldIsRequired( 'value' ) );
		$representationContext = $this->getContextSpy();
		$representationContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $representationContext );

		$changeOps = $deserializer->createEntityChangeOp( [
			'en' => [ 'language' => 'en' ]
		] );

		$this->assertInstanceOf( ChangeOpRepresentationList::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithOfftypeTermDeserializeException_addsViolation() {
		$representationDeserializer = $this->getMock( TermDeserializer::class );
		$representationDeserializer
			->expects( $this->once() )
			->method( 'deserialize' )
			->willThrowException( new InvalidAttributeException( 'value', [ 'hack' ] ) );

		$deserializer = new RepresentationsChangeOpDeserializer( $representationDeserializer );

		$languageContext = $this->getContextSpy();
		$languageContext
			->expects( $this->once() )
			->method( 'addViolation' )
			->with( new JsonFieldHasWrongType( 'string', 'array' ) );
		$representationContext = $this->getContextSpy();
		$representationContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $representationContext );

		$changeOps = $deserializer->createEntityChangeOp( [
			'en' => [ 'language' => 'en', 'value' => [ 'hack' ] ]
		] );

		$this->assertInstanceOf( ChangeOpRepresentationList::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithGenericTermDeserializeException_skipsLanguage() {
		$representationDeserializer = $this->getMock( TermDeserializer::class );
		$representationDeserializer
			->expects( $this->once() )
			->method( 'deserialize' )
			->willThrowException( new DeserializationException( 'so sad' ) );

		$deserializer = new RepresentationsChangeOpDeserializer( $representationDeserializer );

		$languageContext = $this->getContextSpy();
		$languageContext
			->expects( $this->never() )
			->method( 'addViolation' );
		$representationContext = $this->getContextSpy();
		$representationContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $representationContext );

		$changeOps = $deserializer->createEntityChangeOp( [
			'en' => [ 'language' => 'en', 'value' => 'bad things' ]
		] );

		$this->assertInstanceOf( ChangeOpRepresentationList::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	private function getContextSpy() {
		return $this
			->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
	}

}
