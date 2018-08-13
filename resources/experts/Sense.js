module.exports = ( function ( wb, vv ) {
	'use strict';

	var PARENT = wb.experts.Entity;

	/**
	 * `valueview` `Expert` for specifying a reference to a Wikibase Lexeme Sense.
	 * @see jQuery.valueview.expert
	 * @see jQuery.valueview.Expert
	 * @class wikibase.experts.Sense
	 * @extends wikibase.experts.Entity
	 * @license GPL-2.0-or-later
	 */
	var SELF = vv.expert( 'wikibaselexemesense', PARENT, {
		/**
		 * @inheritdoc
		 */
		_init: function () {
			PARENT.prototype._initEntityExpert.call( this );

			this.$input.val(
				this.$input.data( 'entityselector' ).selectedEntity().id
			);
		}
	} );

	/**
	 * @inheritdoc
	 */
	SELF.TYPE = 'sense';

	return SELF;

}( wikibase, jQuery.valueview ) );
