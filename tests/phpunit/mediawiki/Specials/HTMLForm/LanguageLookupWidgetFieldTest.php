<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials\HTMLForm;

use ReflectionClass;
use Wikibase\Lexeme\Specials\HTMLForm\LanguageLookupWidget;
use Wikibase\Lexeme\Specials\HTMLForm\LanguageLookupWidgetField;

/**
 * @covers Wikibase\Lexeme\Specials\HTMLForm\LanguageLookupWidgetField
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class LanguageLookupWidgetFieldTest extends \PHPUnit_Framework_TestCase {

	public function testGetInputWidget_returnsLanguageLookupWidget() {
		$getInputWidget = ( new ReflectionClass( LanguageLookupWidgetField::class ) )
			->getMethod( 'getInputWidget' );
		$getInputWidget->setAccessible( true );

		$this->assertInstanceOf(
			LanguageLookupWidget::class,
			$getInputWidget->invokeArgs( new LanguageLookupWidgetField( [ 'fieldname' => '' ] ), [ [] ] )
		);
	}

}
