<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Store;

use PHPUnit4And6Compat;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataTransfer\NullFormId;
use Wikibase\Lexeme\Store\FormRevisionLookup;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\Store\FormRevisionLookup
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class FormRevisionLookupTest extends TestCase {

	use PHPUnit4And6Compat;

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	/**
	 * @var FormId
	 */
	private $formId;

	protected function setUp() {
		parent::setUp();

		$this->lexemeId = new LexemeId( 'L1' );
		$this->formId = new FormId( 'L1-F1' );
	}

	public function testGivenLexemeId_getEntityRevisionFails() {
		$parentService = $this->getMock( EntityRevisionLookup::class );
		$instance = new FormRevisionLookup( $parentService );

		$this->setExpectedException( ParameterTypeException::class );
		$instance->getEntityRevision( $this->lexemeId );
	}

	public function testGivenFormId_getEntityRevisionCallsParentServiceWithLexemeId() {
		$lexeme = $this->newLexeme();
		$revisionId = 23;

		$parentService = $this->getMock( EntityRevisionLookup::class );
		$parentService->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $this->lexemeId, $revisionId )
			->willReturn( new EntityRevision( $lexeme, $revisionId ) );
		$instance = new FormRevisionLookup( $parentService );

		$result = $instance->getEntityRevision( $this->formId, $revisionId );

		$expectedForm = $lexeme->getForms()->toArray()[0];
		$this->assertEquals( new EntityRevision( $expectedForm, $revisionId ), $result );
	}

	public function testGivenLexemeId_getLatestRevisionIdFails() {
		$parentService = $this->getMock( EntityRevisionLookup::class );
		$instance = new FormRevisionLookup( $parentService );

		$this->setExpectedException( ParameterTypeException::class );
		$instance->getLatestRevisionId( $this->lexemeId );
	}

	public function testGivenNullFormId_lookupIsNotPerformedAndNullReturned() {
		$parentService = $this->getMock( EntityRevisionLookup::class );
		$parentService
			->expects( $this->never() )
			->method( 'getEntityRevision' );

		$formRevisionLookup = new FormRevisionLookup( $parentService );

		$this->assertNull( $formRevisionLookup->getEntityRevision( new NullFormId() ) );
	}

	private function newLexeme() {
		return NewLexeme::havingId( $this->lexemeId )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'representation' )
			)
			->build();
	}

	public function testGivenFormId_getLatestRevisionIdCallsToParentServiceWithLexemeId() {
		$defaultMode = EntityRevisionLookup::LATEST_FROM_REPLICA;

		/** @var EntityRevisionLookup $parentService */
		$parentService = $this->prophesize( EntityRevisionLookup::class );
		$parentService->getLatestRevisionId( $this->lexemeId, $defaultMode )
			->willReturn( LatestRevisionIdResult::concreteRevision( 123 ) );
		$parentService->getEntityRevision( $this->lexemeId, 123, $defaultMode )->willReturn(
			new EntityRevision( $this->newLexeme(), 123 )
		);

		$instance = new FormRevisionLookup( $parentService->reveal() );

		$result = $this->extractConcreteRevision(
			$instance->getLatestRevisionId( $this->formId, $defaultMode )
		);
		$this->assertSame( 123, $result );
	}

	public function testGivenNotExistingFormId_getLatestRevisionIdReturnsNonexistentRevision() {
		$defaultMode = EntityRevisionLookup::LATEST_FROM_REPLICA;

		/** @var EntityRevisionLookup $parentService */
		$parentService = $this->prophesize( EntityRevisionLookup::class );
		$parentService->getLatestRevisionId( $this->lexemeId, $defaultMode )
			->willReturn( LatestRevisionIdResult::concreteRevision( 123 ) );

		$parentService->getEntityRevision( $this->lexemeId, 123, $defaultMode )
			->willReturn( new EntityRevision( $this->newLexeme(), 123 ) );

		$instance = new FormRevisionLookup( $parentService->reveal() );

		$this->assertNonexistentRevision(
			$instance->getLatestRevisionId( new FormId( 'L1-F200' ) )
		);
	}

	private function extractConcreteRevision( LatestRevisionIdResult $result ) {
		$shouldNotBeCalled = function () {
			$this->fail( 'Not a concrete revision given' );
		};

		return $result->onNonexistentEntity( $shouldNotBeCalled )
			->onRedirect( $shouldNotBeCalled )
			->onConcreteRevision( 'intval' )
			->map();
	}

	private function assertNonexistentRevision( LatestRevisionIdResult $result ) {
		$shouldNotBeCalled = function () {
			$this->fail( 'Not a nonexistent revision given' );
		};

		return $result->onRedirect( $shouldNotBeCalled )
			->onConcreteRevision( $shouldNotBeCalled )
			->onNonexistentEntity(
				function () {
					$this->assertTrue( true );
				}
			)
			->map();
	}

}
