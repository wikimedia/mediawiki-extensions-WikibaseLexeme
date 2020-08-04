<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials\HTMLForm;

use HamcrestPHPUnitIntegration;
use OOUI\BlankTheme;
use OOUI\Theme;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\MediaWiki\Specials\HTMLForm\ItemSelectorWidget;
use Wikibase\Lexeme\MediaWiki\Specials\HTMLForm\ItemSelectorWidgetField;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Specials\HTMLForm\ItemSelectorWidgetField
 *
 * @license GPL-2.0-or-later
 */
class ItemSelectorWidgetFieldTest extends TestCase {

	use HamcrestPHPUnitIntegration;

	protected function setUp() : void {
		parent::setUp();
		Theme::setSingleton( new BlankTheme() );
	}

	protected function tearDown() : void {
		Theme::setSingleton( null );
		parent::tearDown();
	}

	public function testGetInputWidget_returnsItemSelectorWidget() {
		$getInputWidget = ( new ReflectionClass( ItemSelectorWidgetField::class ) )
			->getMethod( 'getInputWidget' );
		$getInputWidget->setAccessible( true );

		$this->assertInstanceOf(
			ItemSelectorWidget::class,
			$getInputWidget->invokeArgs( new ItemSelectorWidgetField( [ 'fieldname' => '' ] ), [ [] ] )
		);
	}

	private function getLabelDescriptionLookup() {
		$fakeTerm = new Term( 'en', 'Test Item' );

		$lookup = $this->createMock( LabelDescriptionLookup::class );
		$lookup->method( 'getLabel' )
			->will( $this->returnValue( $fakeTerm ) );

		return $lookup;
	}

	private function getWidgetField( array $params = [] ) {
		$requiredParams = [ 'fieldname' => 'test' ];

		return new ItemSelectorWidgetField(
			array_merge( $requiredParams, $params ),
			new ItemIdParser(),
			$this->getLabelDescriptionLookup()
		);
	}

	public function testGivenValueIsIdOfExistingItem_labelFieldContainsItemsLabelAndId() {
		$widgetField = $this->getWidgetField();

		$widget = $widgetField->getInputOOUI( 'Q123' );

		$expectedLabel = 'Test Item (Q123)';

		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' value='$expectedLabel'/>" )
			) ) )
		);
	}

	public function testGivenValueIsIdOfNotExistingItem_valueGetsPassedToLabelField() {
		$lookup = $this->createMock( LabelDescriptionLookup::class );
		$lookup->method( 'getLabel' )
			->will( $this->returnValue( null ) );

		$widgetField = new ItemSelectorWidgetField(
			[ 'fieldname' => 'test' ],
			new ItemIdParser(),
			$lookup
		);

		$widget = $widgetField->getInputOOUI( 'Q123' );

		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' value='Q123'/>" )
			) ) )
		);
	}

	public function testGivenValueIsNotItemId_valueGetsPassedToLabelField() {
		$widgetField = $this->getWidgetField();

		$widget = $widgetField->getInputOOUI( 'Foo' );

		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' value='Foo'/>" )
			) ) )
		);
	}

	public function testGivenLabelFieldNameGiven_itIsUsedAsName() {
		$widgetField = $this->getWidgetField(
			[ 'name' => 'test-name', 'labelFieldName' => 'custom-name' ]
		);

		$widget = $widgetField->getInputOOUI( '' );

		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' name='custom-name'/>" )
			) ) )
		);
		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='hidden' name='test-name'/>" )
			) ) )
		);
	}

	public function testGivenNoLabelFieldNameGiven_hiddenFieldNameIsUsedAsNameOfLabelField() {
		$widgetField = $this->getWidgetField( [ 'name' => 'test-name' ] );

		$widget = $widgetField->getInputOOUI( '' );

		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' name='test-name'/>" )
			) ) )
		);
		$this->assertThatHamcrest(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='hidden' name='test-name'/>" )
			) ) )
		);
	}

	public function testUsingDefaultAutocompletConfig_disablesNativeBrowserAutocomplete() {
		$widgetField = $this->getWidgetField();

		$this->assertThatHamcrest(
			$widgetField->getInputOOUI( '' )->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input autocomplete='off'/>" )
			) ) )
		);
	}

}
