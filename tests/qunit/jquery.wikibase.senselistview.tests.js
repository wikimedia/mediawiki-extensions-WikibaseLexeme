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

	var createViewElement = function ( messageStub ) {
		var $node = $( '<div><div class="wikibase-lexeme-senses"/></div>' );
		return $node.senselistview( {
			getListItemAdapter: function () {
				return senseviewListItemAdapter;
			},
			getAdder: function ( add, $dom, label, title ) {
				var options = { label: label, title: title };
				return new wb.view.ToolbarFactory().getAddToolbar( options, $dom );
			},
			getMessage: messageStub || function () {}
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

	QUnit.test( 'includes a button to "add sense"', function ( assert ) {
		var translatedMessage = 'adde the lexeme!';
		var messageStub = function ( key ) {
			if ( key === 'wikibase-lexeme-add-sense' ) { return translatedMessage; }
		};

		var $view = createViewElement( messageStub );

		assert.equal( $view.find( '.wikibase-toolbar-button-add' ).text(), translatedMessage );
	} );

}( jQuery, wikibase, QUnit, sinon ) );
