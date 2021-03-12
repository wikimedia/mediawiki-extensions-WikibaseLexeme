<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseEdit;
use Wikibase\Lexeme\MediaWiki\Api\Error\InvalidSenseClaims;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Repo\ChangeOp\ChangeOpStatement;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class EditSenseChangeOpDeserializerTest extends TestCase {

	/**
	 * @var MockObject|GlossesChangeOpDeserializer
	 */
	private $glossesChangeOpDeserializer;

	/**
	 * @var MockObject|ClaimsChangeOpDeserializer
	 */
	private $statementsChangeOpDeserializer;

	protected function setUp(): void {
		parent::setUp();

		$this->glossesChangeOpDeserializer = $this->createMock( GlossesChangeOpDeserializer::class );
		$this->statementsChangeOpDeserializer = $this->createMock( ClaimsChangeOpDeserializer::class );
	}

	public function testCreateEntityChangeOp_yieldsChangeOpSenseEdit() {
		$deserializer = $this->newChangeOpDeserializer();
		$changeOps = $deserializer->createEntityChangeOp( [] );

		$this->assertInstanceOf( ChangeOpSenseEdit::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithOffTypeGlosses_addsViolation() {
		$deserializer = $this->newChangeOpDeserializer();

		$senseContext = $this->createMock( ValidationContext::class );
		$glossesContext = $this->createMock( ValidationContext::class );

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
		$this->glossesChangeOpDeserializer = $this->createMock( GlossesChangeOpDeserializer::class );
		$this->glossesChangeOpDeserializer
			->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( [ 'some' => 'info' ] );
		$deserializer = $this->newChangeOpDeserializer();

		$senseContext = $this->createMock( ValidationContext::class );
		$glossesContext = $this->createMock( ValidationContext::class );

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

	public function testCreateEntityChangeOpWithStatements_callsDownstreamDeserializer() {
		$statementChangeRequest = $this->getValidStatementChangeRequest();
		$statementChangeOp = $this->createStub( ChangeOpStatement::class );
		$this->statementsChangeOpDeserializer = $this->createMock( ClaimsChangeOpDeserializer::class );
		$this->statementsChangeOpDeserializer
			->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $statementChangeRequest )
			->willReturn( $statementChangeOp );

		$deserializer = $this->newChangeOpDeserializer();

		$deserializer->setContext( $this->createStub( ValidationContext::class ) );
		$senseChangeOp = $deserializer->createEntityChangeOp( $statementChangeRequest );

		$this->assertInstanceOf( ChangeOpSenseEdit::class, $senseChangeOp );

		$changeOps = $senseChangeOp->getChangeOps();
		$this->assertCount( 1, $changeOps );
		$this->assertSame( $statementChangeOp, $changeOps[0] );
	}

	public function testCreateEntityChangeOpWithOffTypeStatements_addsViolation() {
		$senseContext = $this->createMock( ValidationContext::class );
		$statementsContext = $this->createMock( ValidationContext::class );
		$senseContext->expects( $this->once() )
			->method( 'at' )
			->with( 'claims' )
			->willReturn( $statementsContext );
		$statementsContext->expects( $this->once() )
			->method( 'addViolation' )
			->with( new JsonFieldHasWrongType( 'array', 'string' ) );

		$deserializer = $this->newChangeOpDeserializer();
		$deserializer->setContext( $senseContext );
		$changeOps = $deserializer->createEntityChangeOp( [ 'claims' => 'asdf' ] );

		$this->assertInstanceOf( ChangeOpSenseEdit::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testGivenStatementChangeOpDeserializerThrows_reportsInvalidClaims() {
		$this->statementsChangeOpDeserializer = $this->createMock( ClaimsChangeOpDeserializer::class );
		$this->statementsChangeOpDeserializer->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->willThrowException( $this->createStub( ChangeOpDeserializationException::class ) );

		$senseContext = $this->createMock( ValidationContext::class );
		$statementsContext = $this->createMock( ValidationContext::class );
		$senseContext->expects( $this->once() )
			->method( 'at' )
			->with( 'claims' )
			->willReturn( $statementsContext );
		$statementsContext->expects( $this->once() )
			->method( 'addViolation' )
			->with( new InvalidSenseClaims() );

		$deserializer = $this->newChangeOpDeserializer();
		$deserializer->setContext( $senseContext );
		$changeOps = $deserializer->createEntityChangeOp( $this->getValidStatementChangeRequest() );

		$this->assertInstanceOf( ChangeOpSenseEdit::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	private function newChangeOpDeserializer() {
		return new EditSenseChangeOpDeserializer(
			$this->glossesChangeOpDeserializer,
			$this->statementsChangeOpDeserializer
		);
	}

	private function getValidStatementChangeRequest(): array {
		return [
			'claims' => [
				'mainsnak' => [ 'snaktype' => 'novalue', 'property' => 'P666' ],
				'type' => 'statement',
				'rank' => 'normal',
			],
		];
	}

}
