( function () {
	'use strict';

	/**
	 * Return a callback to focus the given child of a Vue element.
	 *
	 * Usage:
	 *
	 *     this.$nextTick( focusElement( 'input' ) );
	 *
	 * @param {string} selector CSS selector
	 * @return {Function}
	 */
	function focusElement( selector ) {
		return function () {
			var element = this.$el.querySelector( selector );
			if ( element ) {
				element.focus();
			}
		};
	}

	module.exports = focusElement;
}() );
