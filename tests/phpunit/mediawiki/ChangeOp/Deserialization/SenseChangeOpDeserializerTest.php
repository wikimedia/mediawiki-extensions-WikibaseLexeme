<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\DataAccess\ChangeOp\AddSenseToLexemeChangeOp;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeNotFound;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotLexemeId;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\NullChangeOp;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class SenseChangeOpDeserializerTest extends TestCase {

	public function testRequestWithoutLexemeId_yieldsPureEditSenseChangeOp() {
		$request = [ 'something' ];

		$repr = $this->createMock( EditSenseChangeOpDeserializer::class );

		$editSenseChangeOp = $this->createMock( ChangeOp::class );

		$repr->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $request )
			->willReturn( $editSenseChangeOp );

		$deserializer = new SenseChangeOpDeserializer(
			$this->createMock( EntityLookup::class ),
			$this->createMock( EntityIdParser::class ),
			$repr
		);

		$deserializer->setContext( ValidationContext::create( 'data' ) );

		$this->assertSame(
			$editSenseChangeOp,
			$deserializer->createEntityChangeOp( $request )
		);
	}

	public function testRequestWithLexemeId_yieldsWrappedEditSenseChangeOp() {
		$request = [ 'lexemeId' => 'L4711', 'something' => 'else' ];

		$repr = $this->createMock( EditSenseChangeOpDeserializer::class );

		$editSenseChangeOp = $this->createMock( ChangeOp::class );

		$repr->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $request )
			->willReturn( $editSenseChangeOp );

		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup
			->expects( $this->once() )
			->method( 'getEntity' )
			->willReturn( NewLexeme::havingId( 'L4711' )->build() );

		$idParser = $this->createMock( EntityIdParser::class );
		$idParser->method( 'parse' )
			->willReturnCallback(
				static function ( $id ) {
					return new LexemeId( $id );
				} );

		$deserializer = new SenseChangeOpDeserializer(
			$entityLookup,
			$idParser,
			$repr
		);

		$deserializer->setContext( ValidationContext::create( 'data' ) );

		$changeOp = $deserializer->createEntityChangeOp( $request );

		// TODO Assert that correct lexeme is passed
		$this->assertInstanceOf( AddSenseToLexemeChangeOp::class, $changeOp );
	}

	public function testRequestWithInvalidLexemeId_addsViolation() {
		$request = [ 'lexemeId' => 'foo', 'something' => 'else' ];

		$repr = $this->createMock( EditSenseChangeOpDeserializer::class );

		$editSenseChangeOp = $this->createMock( ChangeOp::class );

		$repr->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $request )
			->willReturn( $editSenseChangeOp );

		$entityLookup = $this->createMock( EntityLookup::class );

		$idParser = $this->createMock( EntityIdParser::class );
		$idParser->method( 'parse' )
			->willThrowException( new EntityIdParsingException() );

		$idContext = $this->createMock( ValidationContext::class );
		$idContext->expects( $this->once() )
			->method( 'addViolation' )
			->with( new ParameterIsNotLexemeId( 'foo' ) );

		$dataContext = $this->createMock( ValidationContext::class );
		$dataContext->expects( $this->once() )
			->method( 'at' )
			->with( 'lexemeId' )
			->willReturn( $idContext );

		$deserializer = new SenseChangeOpDeserializer(
			$entityLookup,
			$idParser,
			$repr
		);

		$deserializer->setContext( $dataContext );

		$changeOp = $deserializer->createEntityChangeOp( $request );

		$this->assertInstanceOf( NullChangeOp::class, $changeOp );
	}

	public function testRequestWithNonLexemeId_addsViolation() {
		$request = [ 'lexemeId' => 'Q2', 'something' => 'else' ];

		$repr = $this->createMock( EditSenseChangeOpDeserializer::class );

		$editSenseChangeOp = $this->createMock( ChangeOp::class );

		$repr->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $request )
			->willReturn( $editSenseChangeOp );

		$entityLookup = $this->createMock( EntityLookup::class );

		$idParser = $this->createMock( EntityIdParser::class );
		$idParser->method( 'parse' )
			->willReturn( new ItemId( 'Q2' ) );

		$idContext = $this->createMock( ValidationContext::class );
		$idContext->expects( $this->once() )
			->method( 'addViolation' )
			->with( new ParameterIsNotLexemeId( 'Q2' ) );

		$dataContext = $this->createMock( ValidationContext::class );
		$dataContext->expects( $this->once() )
			->method( 'at' )
			->with( 'lexemeId' )
			->willReturn( $idContext );

		$deserializer = new SenseChangeOpDeserializer(
			$entityLookup,
			$idParser,
			$repr
		);

		$deserializer->setContext( $dataContext );

		$changeOp = $deserializer->createEntityChangeOp( $request );

		$this->assertInstanceOf( NullChangeOp::class, $changeOp );
	}

	public function testRequestWithIdOfNotExistingLexeme_addsViolation() {
		$request = [ 'lexemeId' => 'L3000', 'something' => 'else' ];

		$repr = $this->createMock( EditSenseChangeOpDeserializer::class );

		$editSenseChangeOp = $this->createMock( ChangeOp::class );

		$repr->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $request )
			->willReturn( $editSenseChangeOp );

		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->method( 'getEntity' )
			->willReturn( null );

		$idParser = $this->createMock( EntityIdParser::class );
		$idParser->method( 'parse' )
			->willReturnCallback(
				static function ( $id ) {
					return new LexemeId( $id );
				} );

		$idContext = $this->createMock( ValidationContext::class );
		$idContext->expects( $this->once() )
			->method( 'addViolation' )
			->with( new LexemeNotFound( new LexemeId( 'L3000' ) ) );

		$dataContext = $this->createMock( ValidationContext::class );
		$dataContext->expects( $this->once() )
			->method( 'at' )
			->with( 'lexemeId' )
			->willReturn( $idContext );

		$deserializer = new SenseChangeOpDeserializer(
			$entityLookup,
			$idParser,
			$repr
		);

		$deserializer->setContext( $dataContext );

		$changeOp = $deserializer->createEntityChangeOp( $request );

		$this->assertInstanceOf( NullChangeOp::class, $changeOp );
	}

}
