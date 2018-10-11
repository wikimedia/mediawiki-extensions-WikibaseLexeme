<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\ValidationContext;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\EditSenseChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class EditSenseChangeOpDeserializerTest extends TestCase {

	public function testCreateEntityChangeOp_yieldsChangeOpSenseEdit() {
		$deserializer = $this->getDeserializer();
		$changeOps = $deserializer->createEntityChangeOp( [] );

		$this->assertInstanceOf( ChangeOpSenseEdit::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithOffTypeGlosses_addsViolation() {
		$deserializer = $this->getDeserializer();

		$senseContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
		$glossesContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();

		$senseContext->expects( $this->once() )
			->method( 'at' )
			->with( 'glosses' )
			->willReturn( $glossesContext );
		$glossesContext->expects( $this->once() )
			->method( 'addViolation' )
			->with( new JsonFieldHasWrongType( 'array', 'string' ) );

		$deserializer->setContext( $senseContext );
		$changeOps = $deserializer->createEntityChangeOp( [ 'glosses' => 'ff' ] );

		$this->assertInstanceOf( ChangeOpSenseEdit::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithGlosses_callsDownstreamDeserializer() {
		$glossesChangeOpDeserializer = $this->getGlossesChangeOpDeserializer();
		$glossesChangeOpDeserializer
			->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( [ 'some' => 'info' ] );
		$deserializer = $this->getDeserializer( $glossesChangeOpDeserializer );

		$senseContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
		$glossesContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();

		$senseContext->expects( $this->once() )
			->method( 'at' )
			->with( 'glosses' )
			->willReturn( $glossesContext );

		$deserializer->setContext( $senseContext );
		$changeOps = $deserializer->createEntityChangeOp( [
			'glosses' => [ 'some' => 'info' ]
		] );

		$this->assertInstanceOf( ChangeOpSenseEdit::class, $changeOps );
		$this->assertCount( 1, $changeOps->getChangeOps() );
	}

	private function getGlossesChangeOpDeserializer() {
		return $this->getMockBuilder( GlossesChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getDeserializer( $glossesChangeOpDeserializer = null ) {
		if ( $glossesChangeOpDeserializer === null ) {
			$glossesChangeOpDeserializer = $this->getGlossesChangeOpDeserializer();
		}

		return new EditSenseChangeOpDeserializer( $glossesChangeOpDeserializer );
	}

}
