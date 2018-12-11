wikibase.lexeme.view.ViewFactoryFactory = ( function ( wb ) {
	'use strict';

	var ViewFactoryFactory = function () {};

	$.extend( ViewFactoryFactory.prototype, {

		/**
		 * @see wikibase.view.ViewFactoryFactory
		 *
		 * @param {boolean} isEditable
		 * @param {[]} factoryArguments
		 *
		 * @returns {wikibase.lexeme.view.ControllerViewFactory|wikibase.lexeme.view.ReadModeViewFactory}
		 */
		getViewFactory: function ( isEditable, factoryArguments ) {
			if ( isEditable ) {
				return this._getControllerViewFactory( factoryArguments );
			}

			return this._getReadModeViewFactory( factoryArguments );
		},

		_getControllerViewFactory: function ( factoryArguments ) {
			return this._getInstance(
				wb.lexeme.view.ControllerViewFactory,
				factoryArguments
			);
		},

		_getReadModeViewFactory: function ( factoryArguments ) {
			factoryArguments.shift();
			factoryArguments.shift();

			return this._getInstance(
				wb.lexeme.view.ReadModeViewFactory,
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
}( wikibase ) );
