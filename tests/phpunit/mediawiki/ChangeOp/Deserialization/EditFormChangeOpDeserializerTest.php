<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ValidationContext;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\Deserialization\EditFormChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class EditFormChangeOpDeserializerTest extends TestCase {

	public function testCreateEntityChangeOp_yieldsChangeOpFormEdit() {
		$deserializer = $this->getDeserializer();
		$changeOps = $deserializer->createEntityChangeOp( [] );

		$this->assertInstanceOf( ChangeOpFormEdit::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithOffTypeRepresentations_addsViolation() {
		$deserializer = $this->getDeserializer();

		$formContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
		$representationsContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();

		$formContext->expects( $this->once() )
			->method( 'at' )
			->with( 'representations' )
			->willReturn( $representationsContext );
		$representationsContext->expects( $this->once() )
			->method( 'addViolation' )
			->with( new JsonFieldHasWrongType( 'array', 'string' ) );

		$deserializer->setContext( $formContext );
		$changeOps = $deserializer->createEntityChangeOp( [ 'representations' => 'ff' ] );

		$this->assertInstanceOf( ChangeOpFormEdit::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithRepresentations_callsDownstreamDeserializer() {
		$representationsChangeOpDeserializer = $this->getRepresentationsChangeOpDeserializer();
		$representationsChangeOpDeserializer
			->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( [ 'some' => 'info' ] );
		$deserializer = $this->getDeserializer( $representationsChangeOpDeserializer );

		$formContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
		$representationsContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();

		$formContext->expects( $this->once() )
			->method( 'at' )
			->with( 'representations' )
			->willReturn( $representationsContext );

		$deserializer->setContext( $formContext );
		$changeOps = $deserializer->createEntityChangeOp( [
			'representations' => [ 'some' => 'info' ]
		] );

		$this->assertInstanceOf( ChangeOpFormEdit::class, $changeOps );
		$this->assertCount( 1, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithOffTypeGrammaticalFeatures_addsViolation() {
		$deserializer = $this->getDeserializer();

		$formContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
		$grammaticalFeaturesContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();

		$formContext->expects( $this->once() )
			->method( 'at' )
			->with( 'grammaticalFeatures' )
			->willReturn( $grammaticalFeaturesContext );
		$grammaticalFeaturesContext->expects( $this->once() )
			->method( 'addViolation' )
			->with( new JsonFieldHasWrongType( 'array', 'string' ) );

		$deserializer->setContext( $formContext );
		$changeOps = $deserializer->createEntityChangeOp( [ 'grammaticalFeatures' => 'ff' ] );

		$this->assertInstanceOf( ChangeOpFormEdit::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithGrammaticalFeatures_callsDownstreamDeserializer() {
		$formContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
		$grammaticalFeaturesContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();

		$formContext->expects( $this->once() )
			->method( 'at' )
			->with( 'grammaticalFeatures' )
			->willReturn( $grammaticalFeaturesContext );

		$itemIdListDeserializer = $this->getItemIdListDeserializer();
		$itemIdListDeserializer
			->expects( $this->once() )
			->method( 'deserialize' )
			->with( [ 'some' => 'info' ], $grammaticalFeaturesContext )
			->willReturn( [] );
		$deserializer = $this->getDeserializer( null, $itemIdListDeserializer );

		$deserializer->setContext( $formContext );
		$changeOps = $deserializer->createEntityChangeOp( [
			'grammaticalFeatures' => [ 'some' => 'info' ]
		] );

		$this->assertInstanceOf( ChangeOpFormEdit::class, $changeOps );
		$this->assertCount( 1, $changeOps->getChangeOps() );
	}

	private function getRepresentationsChangeOpDeserializer() {
		return $this->getMockBuilder( RepresentationsChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getItemIdListDeserializer() {
		return $this->getMockBuilder( ItemIdListDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getDeserializer(
		$representationsChangeOpDeserializer = null,
		$itemIdListDeserializer = null
	) {
		if ( $representationsChangeOpDeserializer === null ) {
			$representationsChangeOpDeserializer = $this->getRepresentationsChangeOpDeserializer();
		}
		if ( $itemIdListDeserializer === null ) {
			$itemIdListDeserializer = $this->getItemIdListDeserializer();
		}

		return new EditFormChangeOpDeserializer(
			$representationsChangeOpDeserializer,
			$itemIdListDeserializer
		);
	}

}
