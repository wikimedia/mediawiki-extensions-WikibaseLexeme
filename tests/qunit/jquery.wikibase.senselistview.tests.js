/**
 * @license GPL-2.0+
 */
( function ( $, wb, QUnit, sinon ) {
	'use strict';

	var senseviewListItemAdapter = wb.tests.getMockListItemAdapter(
		'senseview',
		function () {
		}
	);

	var createViewElement = function () {
		var $node = $( '<div><div class="wikibase-lexeme-senses"/></div>' );
		return $node.senselistview( {
			getListItemAdapter: function () {
				return senseviewListItemAdapter;
			}
		} );
	};

	var getViewFromElement = function ( $view ) {
		return $view.data( 'senselistview' );
	};

	var newView = function () {
		return getViewFromElement( createViewElement() );
	};

	QUnit.module( 'jquery.wikibase.senselistview' );

	QUnit.test( 'Can be created', function ( assert ) {
		var view = newView();

		assert.ok( view instanceof $.wikibase.senselistview );
	} );

	QUnit.test( 'Can be destroyed', function ( assert ) {
		var $view = createViewElement();

		assert.ok( getViewFromElement( $view ) );

		$view.data( 'senselistview' ).destroy();

		assert.notOk( getViewFromElement( $view ) );
	} );

}( jQuery, wikibase, QUnit, sinon ) );
