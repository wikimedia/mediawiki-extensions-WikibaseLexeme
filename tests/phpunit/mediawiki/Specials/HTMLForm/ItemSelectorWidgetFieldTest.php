<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials\HTMLForm;

use OOUI\MediaWikiTheme;
use OOUI\Theme;
use ReflectionClass;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\Specials\HTMLForm\ItemSelectorWidget;
use Wikibase\Lexeme\Specials\HTMLForm\ItemSelectorWidgetField;

/**
 * @covers \Wikibase\Lexeme\Specials\HTMLForm\ItemSelectorWidgetField
 *
 * @license GPL-2.0+
 */
class ItemSelectorWidgetFieldTest extends \PHPUnit_Framework_TestCase {

	public function setUp() {
		parent::setUp();
		Theme::setSingleton( new MediaWikiTheme() );
	}

	public function tearDown() {
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

		$lookup = $this->getMock( LabelDescriptionLookup::class );
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

		assertThat(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' value='$expectedLabel'/>" )
			) ) )
		);
	}

	public function testGivenValueIsIdOfNotExistingItem_valueGetsPassedToLabelField() {
		$lookup = $this->getMock( LabelDescriptionLookup::class );
		$lookup->method( 'getLabel' )
			->will( $this->returnValue( null ) );

		$widgetField = new ItemSelectorWidgetField(
			[ 'fieldname' => 'test' ],
			new ItemIdParser(),
			$lookup
		);

		$widget = $widgetField->getInputOOUI( 'Q123' );

		assertThat(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' value='Q123'/>" )
			) ) )
		);
	}

	public function testGivenValueIsNotItemId_valueGetsPassedToLabelField() {
		$widgetField = $this->getWidgetField();

		$widget = $widgetField->getInputOOUI( 'Foo' );

		assertThat(
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

		assertThat(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' name='custom-name'/>" )
			) ) )
		);
		assertThat(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='hidden' name='test-name'/>" )
			) ) )
		);
	}

	public function testGivenNoLabelFieldNameGiven_hiddenFieldNameIsUsedAsNameOfLabelField() {
		$widgetField = $this->getWidgetField( [ 'name' => 'test-name' ] );

		$widget = $widgetField->getInputOOUI( '' );

		assertThat(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='text' name='test-name'/>" )
			) ) )
		);
		assertThat(
			$widget->toString(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( "<input type='hidden' name='test-name'/>" )
			) ) )
		);
	}

}
