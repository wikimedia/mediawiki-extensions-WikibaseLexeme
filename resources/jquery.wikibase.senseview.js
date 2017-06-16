( function ( $, mw ) {
	'use strict';

	var PARENT = $.Widget;

	/**
	 * Initializes StatementGroupListView on given DOM element
	 * @callback buildStatementGroupListView
	 * @param {wikibase.lexeme.datamodel.Sense}
	 * @param {jQuery} JQuery DOM element
	 */

	/**
	 * @class jQuery.wikibase.senseview
	 * @extends jQuery.ui.Widget
	 * @license GPL-2.0+
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 */
	$.widget( 'wikibase.senseview', PARENT, {
		options: {
			/**
			 * @type {buildStatementGroupListView}
			 */
			buildStatementGroupListView: null
		},

		/**
		 * This method acts as a setter if it is given a Sense object.
		 * Otherwise it returns its value.
		 *
		 * @param {wikibase.lexeme.datamodel.Sense} sense
		 * @return {wikibase.lexeme.datamodel.Sense|undefined}
		 */
		value: function ( sense ) {
			if ( sense instanceof wikibase.lexeme.datamodel.Sense ) {
				this.option( 'value', sense );
				return;
			}

			return this.options.value;
		},

		_create: function () {
			PARENT.prototype._create.call( this );

			this.options.buildStatementGroupListView(
				this.value(),
				$( '.wikibase-statementgrouplistview', this.element )
			);
		},

		getHelpMessage: function () {
			return $.Deferred().resolve( this.options.helpMessage ).promise();
		}
	} );
}( jQuery, mediaWiki ) );
