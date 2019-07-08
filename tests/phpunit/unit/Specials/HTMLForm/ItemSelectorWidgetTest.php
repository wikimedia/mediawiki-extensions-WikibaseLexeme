<?php

namespace Wikibase\Lexeme\Tests\Unit\Specials\HTMLForm;

use HamcrestPHPUnitIntegration;
use OOUI\BlankTheme;
use OOUI\Theme;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\MediaWiki\Specials\HTMLForm\ItemSelectorWidget;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Specials\HTMLForm\ItemSelectorWidget
 *
 * @license GPL-2.0-or-later
 */
class ItemSelectorWidgetTest extends MediaWikiUnitTestCase {

	use HamcrestPHPUnitIntegration;

	public function setUp() {
		parent::setUp();
		Theme::setSingleton( new BlankTheme() );
	}

	public function tearDown() {
		Theme::setSingleton( null );
		parent::tearDown();
	}

	private function getWidget( array $params = [] ) {
		$requiredParams = [ 'fieldname' => 'test=field' ];

		return new ItemSelectorWidget( array_merge( $requiredParams, $params ) );
	}

	public function testHiddenFieldForValueIsCreated() {
		$widget = $this->getWidget();

		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='hidden' class='oo-ui-wikibase-item-selector-value'/>" )
			) ) )
		);
	}

	public function testIncludesTextFild() {
		$widget = $this->getWidget();

		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' class='oo-ui-inputWidget-input'/>" )
			) ) )
		);
	}

	public function testHiddenFieldGetsNameProvided() {
		$expectedName = 'foo';
		$widget = $this->getWidget( [ 'name' => $expectedName ] );

		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='hidden' name='$expectedName'/>" )
			) ) )
		);
	}

	public function testHiddenFieldGetsValueProvided() {
		$expectedValue = 'Q123';
		$widget = $this->getWidget( [ 'value' => $expectedValue ] );

		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='hidden' value='$expectedValue'/>" )
			) ) )
		);
	}

	public function testNameIsPreferredOverFieldnameForHiddenFieldName() {
		$name = 'foo';
		$fieldName = 'bar';
		$widget = $this->getWidget( [ 'name' => $name, 'fieldname' => $fieldName ] );

		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='hidden' name='$name'/>" )
			) ) )
		);
	}

	public function testLabelTextFieldGetsNameProvided() {
		$expectedName = 'testLabel';
		$widget = $this->getWidget( [ 'labelFieldName' => $expectedName ] );

		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' name='$expectedName'/>" )
			) ) )
		);
	}

	public function testLabelTextFieldShowsValueProvided() {
		$expectedValue = 'Foo Item (Q123)';
		$widget = $this->getWidget( [ 'labelFieldValue' => $expectedValue ] );

		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' value='$expectedValue'/>" )
			) ) )
		);
	}

	public function testLabelTextFieldShowsHiddenFieldsValueIfNoLabelValueProvided() {
		$expectedValue = 'Q123';
		$widget = $this->getWidget( [ 'value' => $expectedValue ] );

		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' value='$expectedValue'/>" )
			) ) )
		);
	}

}
