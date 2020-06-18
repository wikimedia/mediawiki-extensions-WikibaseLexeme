module.exports = ( function ( wb, vv ) {
	'use strict';

	var PARENT = wb.experts.Entity;

	/**
	 * `valueview` `Expert` for specifying a reference to a Wikibase Lexeme Form.
	 *
	 * @see jQuery.valueview.expert
	 * @see jQuery.valueview.Expert
	 * @class wikibase.experts.Form
	 * @extends wikibase.experts.Entity
	 * @license GPL-2.0-or-later
	 */
	var SELF = vv.expert( 'wikibaselexemeform', PARENT, {
		/**
		 * @inheritdoc
		 */
		_init: function () {
			PARENT.prototype._initEntityExpert.call( this );
		}
	} );

	/**
	 * @inheritdoc
	 */
	SELF.TYPE = 'form';

	return SELF;

}( wikibase, $.valueview ) );
