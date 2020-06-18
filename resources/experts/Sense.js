module.exports = ( function ( wb, vv ) {
	'use strict';

	var PARENT = wb.experts.Entity;

	/**
	 * `valueview` `Expert` for specifying a reference to a Wikibase Lexeme Sense.
	 *
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
			var entity = this.$input.data( 'entityselector' );
			PARENT.prototype._initEntityExpert.call( this );

			if ( entity ) {
				entity = entity.selectedEntity();
				this.$input.val( entity.id );
			}
		}
	} );

	/**
	 * @inheritdoc
	 */
	SELF.TYPE = 'sense';

	return SELF;

}( wikibase, $.valueview ) );
