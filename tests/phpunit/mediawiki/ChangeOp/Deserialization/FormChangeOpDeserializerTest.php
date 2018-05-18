<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\ChangeOp\AddFormToLexemeChangeOp;
use Wikibase\Lexeme\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\FormChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOps;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\Deserialization\FormChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class FormChangeOpDeserializerTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testRequestWithoutLexemeId_yieldsPureEditFormChangeOp() {
		$request = [ 'something' ];

		$repr = $this->getMockBuilder( EditFormChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();

		$editFormChangeOp = $this->getMock( ChangeOp::class );

		$repr->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $request )
			->willReturn( $editFormChangeOp );

		$deserializer = new FormChangeOpDeserializer(
			$this->getMock( EntityLookup::class ),
			$repr
		);

		$deserializer->setContext( ValidationContext::create( 'data' ) );

		$this->assertSame(
			$editFormChangeOp,
			$deserializer->createEntityChangeOp( $request )
		);
	}

	public function testRequestWithLexemeId_yieldsWrappedEditFormChangeOp() {
		$request = [ 'lexemeId' => 'L4711', 'something' => 'else' ];

		$repr = $this->getMockBuilder( EditFormChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();

		$editFormChangeOp = $this->getMock( ChangeOp::class );

		$repr->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $request )
			->willReturn( $editFormChangeOp );

		$entityLookup = $this->getMock( EntityLookup::class );
		$entityLookup
			->expects( $this->once() )
			->method( 'getEntity' )
			->willReturn( NewLexeme::havingId( 'L4711' )->build() );

		$deserializer = new FormChangeOpDeserializer(
			$entityLookup,
			$repr
		);

		$deserializer->setContext( ValidationContext::create( 'data' ) );

		/**
		 * @var ChangeOps $changeOps
		 */
		$changeOps = $deserializer->createEntityChangeOp( $request );
		$changeOpsArray = $changeOps->getChangeOps();

		// TODO Assert that correct lexeme is passed
		$this->assertInstanceOf( AddFormToLexemeChangeOp::class, $changeOpsArray[0] );

		$this->assertSame( $editFormChangeOp, $changeOpsArray[1] );
	}

}
