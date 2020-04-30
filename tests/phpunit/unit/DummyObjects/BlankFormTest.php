<?php

namespace Wikibase\Lexeme\Tests\Unit\DummyObjects;

use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\DummyObjects\DummyFormId;
use Wikibase\Lexeme\Domain\DummyObjects\NullFormId;
use Wikibase\Lexeme\Domain\Model\FormId;

/**
 * @covers \Wikibase\Lexeme\Domain\DummyObjects\BlankForm
 *
 * @license GPL-2.0-or-later
 */
class BlankFormTest extends MediaWikiUnitTestCase {

	public function testConstructedWithNullFormId() {
		$this->assertInstanceOf( NullFormId::class, ( new BlankForm() )->getId() );
	}

	public function testSetsDummyIdFromFormId() {
		$blankForm = new BlankForm();
		$formId = new FormId( 'L1-F2' );
		$blankForm->setId( $formId );

		$this->assertInstanceOf( DummyFormId::class, $blankForm->getId() );
		$this->assertSame( $formId->getSerialization(), $blankForm->getId()->getSerialization() );
	}

}
