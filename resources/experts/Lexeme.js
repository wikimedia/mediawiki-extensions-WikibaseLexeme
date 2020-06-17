module.exports = ( function ( wb, vv ) {
	'use strict';

	var PARENT = wb.experts.Entity;

	/**
	 * `valueview` `Expert` for specifying a reference to a Wikibase `Lexeme`.
	 *
	 * @see jQuery.valueview.expert
	 * @see jQuery.valueview.Expert
	 * @class wikibase.experts.Lexeme
	 * @extends wikibase.experts.Entity
	 * @license GPL-2.0-or-later
	 */
	var SELF = vv.expert( 'wikibaselexeme', PARENT, {
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
	SELF.TYPE = 'lexeme';

	return SELF;

}( wikibase, $.valueview ) );
