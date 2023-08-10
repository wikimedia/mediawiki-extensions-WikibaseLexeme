/**
 * @license GPL-2.0-or-later
 */

( function ( wb ) {
	'use strict';

	var formViewListItemAdapter = wb.tests.getMockListItemAdapter(
		'lexemeformview',
		function () {
		}
	);

	var createViewElement = function ( getAdder, message ) {
		var $node = $( '<div><div class="wikibase-lexeme-forms"/></div>' );
		return $node.lexemeformlistview( {
			getListItemAdapter: function () {
				return formViewListItemAdapter;
			},
			getAdder: getAdder || function () {},
			getMessage: message || function () { return 'localize me'; }
		} );
	};

	var getViewFromElement = function ( $view ) {
		return $view.data( 'lexemeformlistview' );
	};

	var newView = function () {
		return getViewFromElement( createViewElement() );
	};

	QUnit.module( 'jquery.wikibase.lexemeformlistview' );

	QUnit.test( 'Can be created', function ( assert ) {
		var view = newView();

		assert.true( view instanceof $.wikibase.lexemeformlistview );
	} );

	QUnit.test( 'enterNewItem adds new list item', function ( assert ) {
		var view = newView(),
			listItemAdapterSpy = sinon.spy( formViewListItemAdapter, 'newListItem' );

		assert.false( listItemAdapterSpy.called );

		view.enterNewItem();

		assert.true( listItemAdapterSpy.called );
		listItemAdapterSpy.restore();
	} );

	QUnit.test( 'renders a localized add toolbar to add a form', function ( assert ) {
		var messageKey = 'wikibaselexeme-add-form';
		var localizedMessage = 'some message';
		var getAdder = sinon.spy();
		var message = sinon.stub();

		message.withArgs( messageKey ).returns( localizedMessage );
		message.throws( 'Wrong argument to message()' );

		createViewElement( getAdder, message );

		assert.true( getAdder.calledOnce );
		assert.strictEqual( localizedMessage, getAdder.lastCall.args[ 2 ] );
	} );

	QUnit.test( 'Can be destroyed', function ( assert ) {
		var $view = createViewElement();

		assert.true( !!getViewFromElement( $view ) );

		$view.data( 'lexemeformlistview' ).destroy();

		assert.strictEqual( undefined, getViewFromElement( $view ) );
	} );

}( wikibase ) );
