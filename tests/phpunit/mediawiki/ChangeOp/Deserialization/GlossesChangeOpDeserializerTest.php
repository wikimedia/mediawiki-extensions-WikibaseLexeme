<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use ApiUsageException;
use PHPUnit\Framework\TestCase;
use Status;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveSenseGloss;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGloss;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGlossList;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\StringNormalizer;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\GlossesChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class GlossesChangeOpDeserializerTest extends TestCase {

	public function testCreateEntityChangeOpEmpty_yieldsZeroChangeOpGlossList() {
		$glossDeserializer = $this->createMock( TermDeserializer::class );
		$validator = $this->newDummyValidator();

		$deserializer = new GlossesChangeOpDeserializer(
			$glossDeserializer,
			new StringNormalizer(),
			$validator
		);
		$changeOps = $deserializer->createEntityChangeOp( [] );

		$this->assertInstanceOf( ChangeOpGlossList::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithValidTerm_yieldsChangeOpGlossList() {
		$glossDeserializer = $this->createMock( TermDeserializer::class );
		$validator = $this->newDummyValidator();

		$glossDeserializer
			->expects( $this->once() )
			->method( 'deserialize' )
			->with( [ 'language' => 'en', 'value' => 'smth' ] )
			->willReturn( new Term( 'en', 'smth' ) );

		$deserializer = new GlossesChangeOpDeserializer(
			$glossDeserializer,
			new StringNormalizer(),
			$validator
		);

		$languageContext = $this->getContextSpy();
		$glossContext = $this->getContextSpy();
		$glossContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $glossContext );

		$changeOps = $deserializer->createEntityChangeOp( [
			'en' => [ 'language' => 'en', 'value' => 'smth' ]
		] );

		$this->assertInstanceOf( ChangeOpGlossList::class, $changeOps );
		$this->assertCount( 1, $changeOps->getChangeOps() );
		$this->assertInstanceOf( ChangeOpGloss::class, $changeOps->getChangeOps()[0] );
	}

	public function testCreateEntityChangeOpWithValidTerm_trimsGlossValuesToNFC() {
		$glossDeserializer = $this->createMock( TermDeserializer::class );
		$validator = $this->newDummyValidator();

		$glossDeserializer
			->expects( $this->once() )
			->method( 'deserialize' )
			->with( [ 'language' => 'en', 'value' => 'smth' ] )
			->willReturn( new Term( 'en', 'smth' ) );

		$deserializer = new GlossesChangeOpDeserializer(
			$glossDeserializer,
			new StringNormalizer(),
			$validator
		);

		$languageContext = $this->getContextSpy();
		$glossContext = $this->getContextSpy();
		$glossContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $glossContext );

		$changeOps = $deserializer->createEntityChangeOp( [
			'en' => [ 'language' => 'en', 'value' => " \t smth \v\n " ]
		] );

		$this->assertInstanceOf( ChangeOpGlossList::class, $changeOps );
		$this->assertCount( 1, $changeOps->getChangeOps() );
		$this->assertInstanceOf( ChangeOpGloss::class, $changeOps->getChangeOps()[0] );
	}

	public function testCreateEntityChangeOpWithRemoval_yieldsChangeOpGlossList() {
		$glossDeserializer = $this->createMock( TermDeserializer::class );
		$validator = $this->newDummyValidator();

		$deserializer = new GlossesChangeOpDeserializer(
			$glossDeserializer,
			new StringNormalizer(),
			$validator
		);

		$languageContext = $this->getContextSpy();
		$glossContext = $this->getContextSpy();
		$glossContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $glossContext );

		$changeOps = $deserializer->createEntityChangeOp( [
			'en' => [ 'language' => 'en', 'remove' => '' ]
		] );

		$this->assertInstanceOf( ChangeOpGlossList::class, $changeOps );
		$this->assertCount( 1, $changeOps->getChangeOps() );
		$this->assertInstanceOf( ChangeOpRemoveSenseGloss::class, $changeOps->getChangeOps()[0] );
	}

	/**
	 * @expectedException \ApiUsageException
	 */
	public function testGivenChangeValidationFails_exceptionIsThrownInsteadOfCreatingChangeOp() {
		$glossDeserializer = $this->createMock( TermDeserializer::class );
		$validator = $this->newDummyValidator();
		$validator->method( 'validate' )
			->willThrowException(
				new ApiUsageException( null, Status::newFatal( 'some-validation-error' ) )
			);

		$deserializer = new GlossesChangeOpDeserializer(
			$glossDeserializer,
			new StringNormalizer(),
			$validator
		);

		$languageContext = $this->getContextSpy();
		$glossContext = $this->getContextSpy();
		$glossContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $glossContext );

		$deserializer->createEntityChangeOp( [
			'en' => [ 'language' => 'en', 'value' => 'bad things' ]
		] );
	}

	private function getContextSpy() {
		return $this
			->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return LexemeTermSerializationValidator|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function newDummyValidator() {
		$validator = $this->getMockBuilder( LexemeTermSerializationValidator::class )
			->disableOriginalConstructor()
			->getMock();
		return $validator;
	}

}
