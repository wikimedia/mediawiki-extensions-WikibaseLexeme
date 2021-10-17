// Temporary, until merging this with jquery.wikibase.lexemeview
wikibase.lexeme.view = {};

wikibase.lexeme.view.ViewFactoryFactory = ( function () {
	'use strict';

	var ViewFactoryFactory = function () {},
		ControllerViewFactory = require( './ControllerViewFactory.js' ),
		ReadModeViewFactory = require( './ReadModeViewFactory.js' );

	$.extend( ViewFactoryFactory.prototype, {

		/**
		 * @see wikibase.view.ViewFactoryFactory
		 *
		 * @param {boolean} isEditable
		 * @param {[]} factoryArguments
		 *
		 * @return {ControllerViewFactory|ReadModeViewFactory}
		 */
		getViewFactory: function ( isEditable, factoryArguments ) {
			if ( isEditable ) {
				return this._getControllerViewFactory( factoryArguments );
			}

			return this._getReadModeViewFactory( factoryArguments );
		},

		_getControllerViewFactory: function ( factoryArguments ) {
			return this._getInstance(
				ControllerViewFactory,
				factoryArguments
			);
		},

		_getReadModeViewFactory: function ( factoryArguments ) {
			factoryArguments.shift();
			factoryArguments.shift();

			return this._getInstance(
				ReadModeViewFactory,
				factoryArguments
			);
		},

		_getInstance: function ( clazz, args ) {
			args.unshift( null );

			return new ( Function.prototype.bind.apply(
				clazz,
				args
			) )();
		}

	} );

	return ViewFactoryFactory;
}() );

module.exports = wikibase.lexeme.view.ViewFactoryFactory;
