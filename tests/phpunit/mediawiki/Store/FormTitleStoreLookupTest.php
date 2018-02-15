<?php

namespace Wikibase\Lexeme\Tests\Store;

use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Store\FormTitleStoreLookup;
use Wikibase\Repo\Store\EntityTitleStoreLookup;

/**
 * @covers \Wikibase\Lexeme\Store\FormTitleStoreLookup
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class FormTitleStoreLookupTest extends TestCase {

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

	public function testGivenLexemeId_getTitleForIdFails() {
		$instance = new FormTitleStoreLookup( $this->newParentService() );

		$this->setExpectedException( UnexpectedValueException::class );
		$instance->getTitleForId( $this->lexemeId );
	}

	public function testGivenFormId_getTitleForIdCallsParentServiceWithLexemeId() {
		$expectedId = $this->lexemeId;
		$instance = new FormTitleStoreLookup( $this->newParentService( $expectedId ) );

		$result = $instance->getTitleForId( $this->formId );
		$this->assertSame( 'fromParentService', $result );
	}

	/**
	 * @param LexemeId $expectedId
	 *
	 * @return EntityTitleStoreLookup
	 */
	private function newParentService( LexemeId $expectedId = null ) {
		$parentService = $this->getMock( EntityTitleStoreLookup::class );

		if ( $expectedId ) {
			$parentService->expects( $this->once() )
				->method( 'getTitleForId' )
				->with( $expectedId )
				->willReturn( 'fromParentService' );
		} else {
			$parentService->expects( $this->never() )
				->method( 'getTitleForId' );
		}

		return $parentService;
	}

}
