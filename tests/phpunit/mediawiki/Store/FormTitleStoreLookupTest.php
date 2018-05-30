<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Store;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Title;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Store\FormTitleStoreLookup;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\Store\FormTitleStoreLookup
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class FormTitleStoreLookupTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testGivenLexemeId_getTitleForIdFails() {
		$instance = new FormTitleStoreLookup( $this->getMock( EntityTitleStoreLookup::class ) );

		$this->setExpectedException( ParameterTypeException::class );
		$instance->getTitleForId( new LexemeId( 'L1' ) );
	}

	public function testGivenFormId_getTitleForIdCallsParentServiceWithLexemeId() {
		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$title = $this->getMock( Title::class );
		$title->method( 'setFragment' )
			->with( '#' . $formId->getSerialization() );

		$parentLookup = $this->getMock( EntityTitleStoreLookup::class );
		$parentLookup->method( 'getTitleForId' )
			->with( $lexemeId )
			->willReturn( $title );

		$instance = new FormTitleStoreLookup( $parentLookup );

		$result = $instance->getTitleForId( $formId );
		$this->assertSame( $title, $result );
	}

	public function testGivenNoTitleForLexeme_getTitleForIdReturnsNull() {
		$parentLookup = $this->getMock( EntityTitleStoreLookup::class );
		$parentLookup->method( 'getTitleForId' )
			->willReturn( null );

		$lookup = new FormTitleStoreLookup( $parentLookup );

		$this->assertNull( $lookup->getTitleForId( new FormId( 'L66-F1' ) ) );
	}

}
