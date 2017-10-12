<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials\HTMLForm;

use OOUI\MediaWikiTheme;
use OOUI\Theme;
use Wikibase\Lexeme\Specials\HTMLForm\ItemSelectorWidget;

/**
 * @covers \Wikibase\Lexeme\Specials\HTMLForm\ItemSelectorWidget
 *
 * @license GPL-2.0+
 */
class ItemSelectorWidgetTest extends \PHPUnit_Framework_TestCase {

	public function setUp() {
		parent::setUp();
		Theme::setSingleton( new MediaWikiTheme() );
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

		assertThat(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='hidden' class='oo-ui-wikibase-item-selector-value'/>" )
			) ) )
		);
	}

	public function testIncludesTextFild() {
		$widget = $this->getWidget();

		assertThat(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' class='oo-ui-inputWidget-input'/>" )
			) ) )
		);
	}

	public function testHiddenFieldGetsNameProvided() {
		$expectedName = 'foo';
		$widget = $this->getWidget( [ 'name' => $expectedName ] );

		assertThat(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='hidden' name='$expectedName'/>" )
			) ) )
		);
	}

	public function testHiddenFieldGetsValueProvided() {
		$expectedValue = 'Q123';
		$widget = $this->getWidget( [ 'value' => $expectedValue ] );

		assertThat(
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

		assertThat(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='hidden' name='$name'/>" )
			) ) )
		);
	}

	public function testLabelTextFieldGetsNameProvided() {
		$expectedName = 'testLabel';
		$widget = $this->getWidget( [ 'labelFieldName' => $expectedName ] );

		assertThat(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' name='$expectedName'/>" )
			) ) )
		);
	}

	public function testLabelTextFieldShowsValueProvided() {
		$expectedValue = 'Foo Item (Q123)';
		$widget = $this->getWidget( [ 'labelFieldValue' => $expectedValue ] );

		assertThat(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' value='$expectedValue'/>" )
			) ) )
		);
	}

	public function testLabelTextFieldShowsHiddenFieldsValueIfNoLabelValueProvided() {
		$expectedValue = 'Q123';
		$widget = $this->getWidget( [ 'value' => $expectedValue ] );

		assertThat(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' value='$expectedValue'/>" )
			) ) )
		);
	}

}
