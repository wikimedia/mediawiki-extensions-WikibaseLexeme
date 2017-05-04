/**
 * @license GPL-2.0+
 */
( function( $, wb, QUnit, sinon ) {
	'use strict';

	var lexemeformviewListItemAdapter = wb.tests.getMockListItemAdapter(
		'lexemeformview',
		function() {
		}
	);

	var createViewElement = function() {
		var $node = $( '<div><div class="wikibase-lexeme-forms"/></div>' );
		return $node.lexemeformlistview( {
			getListItemAdapter: function() {
				return lexemeformviewListItemAdapter;
			},
			getAdder: function() {}
		} );
	};

	var getViewFromElement = function( $view ) {
		return $view.data( 'lexemeformlistview' );
	};

	var newView = function() {
		return getViewFromElement( createViewElement() );
	};

	QUnit.module( 'jquery.wikibase.lexemeformlistview' );

	QUnit.test( 'Can be created', function( assert ) {
		var view = newView();

		assert.ok( view instanceof $.wikibase.lexemeformlistview );
	} );

	QUnit.test( 'enterNewItem adds new list item', function( assert ) {
		var view = newView(),
			listItemAdapterSpy = sinon.spy( lexemeformviewListItemAdapter, 'newListItem' );

		assert.notOk( listItemAdapterSpy.called );

		view.enterNewItem();

		assert.ok( listItemAdapterSpy.called );
		listItemAdapterSpy.restore();
	} );

	QUnit.test( 'Can be destroyed', function( assert ) {
		var $view = createViewElement();

		assert.ok( getViewFromElement( $view ) );

		$view.data( 'lexemeformlistview' ).destroy();

		assert.notOk( getViewFromElement( $view ) );
	} );

}( jQuery, wikibase, QUnit, sinon ) );
