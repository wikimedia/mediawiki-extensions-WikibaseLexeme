/**
 * @license GPL-2.0-or-later
 */
describe( 'focusElement', function () {
	'use strict';

	var expect = require( 'unexpected' ).clone(),
		sinon = require( 'sinon' ),
		focusElement = require( '../../resources/focusElement.js' );

	it( 'returns a callback without doing anything else', function () {
		var callback = focusElement( 'selector' );
		expect( callback, 'to be a', 'function' );
	} );

	describe( 'callback', function () {
		it( 'calls focus on selected element', function () {
			var selector = 'selector';
			var callback = focusElement( selector );
			var element = { focus: sinon.spy() };
			var $el = { querySelector: sinon.stub().returns( element ) };
			callback.call( { $el: $el } );
			expect( $el.querySelector.calledOnceWith( selector ), 'to be truthy' );
			expect( element.focus.calledOnce, 'to be truthy' );
		} );

		it( 'can handle missing element', function () {
			var selector = 'selector';
			var callback = focusElement( selector );
			var $el = { querySelector: sinon.stub().returns( null ) };
			callback.call( { $el: $el } );
			expect( $el.querySelector.calledOnceWith( selector ), 'to be truthy' );
		} );
	} );

} );
