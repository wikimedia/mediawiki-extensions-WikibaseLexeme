<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataTransfer\NullFormId;
use Wikibase\Lexeme\Store\FormRevisionLookup;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\Store\FormRevisionLookup
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class FormRevisionLookupTest extends TestCase {

	use \PHPUnit4And6Compat;

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

	public function testGivenFormId_getLatestRevisionIdCallsToParentServiceWithLexemeId() {
		$parentService = $this->getMock( EntityRevisionLookup::class );
		$parentService->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $this->lexemeId )
			->willReturn( 'fromParentService' );
		$parentService->method( 'getEntityRevision' )
			->with( $this->lexemeId )
			->willReturn( new EntityRevision( $this->newLexeme(), 123 ) );
		$instance = new FormRevisionLookup( $parentService );

		$result = $instance->getLatestRevisionId( $this->formId );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenNotExistingFormId_getLatestRevisionIdReturnsFalse() {
		$parentService = $this->getMock( EntityRevisionLookup::class );
		$parentService->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $this->lexemeId )
			->willReturn( 'fromParentService' );
		$parentService->method( 'getEntityRevision' )
			->with( $this->lexemeId )
			->willReturn( new EntityRevision( $this->newLexeme(), 123 ) );
		$instance = new FormRevisionLookup( $parentService );

		$this->assertFalse( $instance->getLatestRevisionId( new FormId( 'L1-F200' ) ) );
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
		$lexeme = new Lexeme( $this->lexemeId );
		$lexeme->addForm( new TermList( [ new Term( 'en', 'representation' ) ] ), [] );
		return $lexeme;
	}

}
