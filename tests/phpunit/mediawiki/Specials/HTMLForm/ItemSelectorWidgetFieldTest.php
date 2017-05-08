<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials\HTMLForm;

use ReflectionClass;
use Wikibase\Lexeme\Specials\HTMLForm\ItemSelectorWidget;
use Wikibase\Lexeme\Specials\HTMLForm\ItemSelectorWidgetField;

/**
 * @covers Wikibase\Lexeme\Specials\HTMLForm\ItemSelectorWidgetField
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class ItemSelectorWidgetFieldTest extends \PHPUnit_Framework_TestCase {

	public function testGetInputWidget_returnsItemSelectorWidget() {
		$getInputWidget = ( new ReflectionClass( ItemSelectorWidgetField::class ) )
			->getMethod( 'getInputWidget' );
		$getInputWidget->setAccessible( true );

		$this->assertInstanceOf(
			ItemSelectorWidget::class,
			$getInputWidget->invokeArgs( new ItemSelectorWidgetField( [ 'fieldname' => '' ] ), [ [] ] )
		);
	}

}
