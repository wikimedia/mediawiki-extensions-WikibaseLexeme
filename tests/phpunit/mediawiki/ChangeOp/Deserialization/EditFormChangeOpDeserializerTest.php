<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\Validators\CompositeValidator;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class EditFormChangeOpDeserializerTest extends TestCase {

	/**
	 * @var MockObject|RepresentationsChangeOpDeserializer
	 */
	private $representationsChangeOpDeserializer;

	/**
	 * @var MockObject|ItemIdListDeserializer
	 */
	private $itemIdListDeserializer;

	/**
	 * @var MockObject|ClaimsChangeOpDeserializer
	 */
	private $statementsChangeOpDeserializer;

	protected function setUp(): void {
		parent::setUp();

		$this->representationsChangeOpDeserializer = $this->createStub( RepresentationsChangeOpDeserializer::class );
		$this->itemIdListDeserializer = $this->createStub( ItemIdListDeserializer::class );
		$this->statementsChangeOpDeserializer = $this->createStub( ClaimsChangeOpDeserializer::class );
	}

	public function testCreateEntityChangeOp_yieldsChangeOpFormEdit() {
		$deserializer = $this->newChangeOpDeserializer();
		$changeOps = $deserializer->createEntityChangeOp( [] );

		$this->assertInstanceOf( ChangeOpFormEdit::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithOffTypeRepresentations_addsViolation() {
		$deserializer = $this->newChangeOpDeserializer();

		$formContext = $this->createMock( ValidationContext::class );
		$representationsContext = $this->createMock( ValidationContext::class );

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
		$this->representationsChangeOpDeserializer =
			$this->createMock( RepresentationsChangeOpDeserializer::class );
		$this->representationsChangeOpDeserializer
			->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( [ 'some' => 'info' ] );
		$deserializer = $this->newChangeOpDeserializer();

		$formContext = $this->createMock( ValidationContext::class );
		$formContext->expects( $this->once() )
			->method( 'at' )
			->with( 'representations' )
			->willReturn( $this->createMock( ValidationContext::class ) );

		$deserializer->setContext( $formContext );
		$changeOps = $deserializer->createEntityChangeOp( [
			'representations' => [ 'some' => 'info' ]
		] );

		$this->assertInstanceOf( ChangeOpFormEdit::class, $changeOps );
		$this->assertCount( 1, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithOffTypeGrammaticalFeatures_addsViolation() {
		$deserializer = $this->newChangeOpDeserializer();

		$formContext = $this->createMock( ValidationContext::class );
		$grammaticalFeaturesContext = $this->createMock( ValidationContext::class );

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
		$formContext = $this->createMock( ValidationContext::class );
		$grammaticalFeaturesContext = $this->createMock( ValidationContext::class );

		$formContext->expects( $this->once() )
			->method( 'at' )
			->with( 'grammaticalFeatures' )
			->willReturn( $grammaticalFeaturesContext );

		$this->itemIdListDeserializer = $this->createMock( ItemIdListDeserializer::class );
		$this->itemIdListDeserializer
			->expects( $this->once() )
			->method( 'deserialize' )
			->with( [ 'some' => 'info' ], $grammaticalFeaturesContext )
			->willReturn( [] );
		$deserializer = $this->newChangeOpDeserializer();

		$deserializer->setContext( $formContext );
		$changeOps = $deserializer->createEntityChangeOp( [
			'grammaticalFeatures' => [ 'some' => 'info' ]
		] );

		$this->assertInstanceOf( ChangeOpFormEdit::class, $changeOps );
		$this->assertCount( 1, $changeOps->getChangeOps() );
	}

	public function testGivenChangeRequestContainsClaims_callsDownstreamDeserializer() {
		$changeRequest = [ 'claims' => [] ];
		$this->statementsChangeOpDeserializer = $this->createMock( ClaimsChangeOpDeserializer::class );
		$this->statementsChangeOpDeserializer->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $changeRequest );

		$deserializer = $this->newChangeOpDeserializer();
		$deserializer->setContext( $this->createMock( ValidationContext::class ) );

		$deserializer->createEntityChangeOp( $changeRequest );
	}

	private function newChangeOpDeserializer() {
		return new EditFormChangeOpDeserializer(
			$this->representationsChangeOpDeserializer,
			$this->itemIdListDeserializer,
			$this->statementsChangeOpDeserializer,
			new CompositeValidator( [] )
		);
	}

}
