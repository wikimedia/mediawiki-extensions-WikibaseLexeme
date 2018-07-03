( function ( $, mw, wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

	var GlossWidget = require( 'wikibase.lexeme.widgets.GlossWidget' );

	/**
	 * Initializes StatementGroupListView on given DOM element
	 * @callback buildStatementGroupListView
	 * @param {wikibase.lexeme.datamodel.Sense}
	 * @param {jQuery} JQuery DOM element
	 */

	/**
	 * @class jQuery.wikibase.senseview
	 * @extends jQuery.ui.Widget
	 * @license GPL-2.0-or-later
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 */
	$.widget( 'wikibase.senseview', PARENT, {
		options: {
			template: 'wikibase-lexeme-sense',
			templateParams: [
				function () {
					return 'ID-GOES-HERE';
				},
				function () {
					return $( '<div class="wikibase-lexeme-sense-glosses"></div>' );
				},
				function () {
					return 'STATEMENTS-GO-HERE';
				}
			],

			/**
			 * @type {buildStatementGroupListView}
			 */
			buildStatementGroupListView: null
		},

		glossWidget: null,

		/**
		 * This method acts as a setter if it is given a Sense object.
		 * Otherwise it returns its value.
		 *
		 * @param {wikibase.lexeme.datamodel.Sense} sense
		 * @return {wikibase.lexeme.datamodel.Sense|undefined}
		 */
		value: function ( sense ) {
			if ( sense instanceof wb.lexeme.datamodel.Sense ) {
				this.option( 'value', sense );
				return;
			}

			if ( !this.isInEditMode() ) {
				return this.options.value;
			}

			return new wb.lexeme.datamodel.Sense(
				this.options.value ? this.options.value.getId() : null,
				arrayToTermMap( this.glossWidget.glosses )
			);
		},

		_create: function () {
			PARENT.prototype._create.call( this );

			this.options.buildStatementGroupListView(
				this.value(),
				$( '.wikibase-statementgrouplistview', this.element )
			);

			this.glossWidget = GlossWidget.applyGlossWidget(
				$( '.wikibase-lexeme-sense-glosses', this.element )[ 0 ],
				termMapToArray( this.value().getGlosses() ),
				function () {
					this._trigger( 'change' );
				}.bind( this ),
				mw,
				$.util.getDirectionality
			);
		},

		_startEditing: function () {
			this.glossWidget.edit();

			return $.Deferred().resolve().promise();
		},

		_stopEditing: function ( dropValue ) {
			this.glossWidget.stopEditing();
			if ( dropValue ) {
				this.glossWidget.glosses = termMapToArray(
					this.value().getGlosses()
				);
			}

			return $.Deferred().resolve().promise();
		}
	} );

	function arrayToTermMap( glosses ) {
		var result = new wb.datamodel.TermMap();

		glosses.forEach( function ( gloss ) {
			try {
				result.setItem(
					gloss.language,
					new wb.datamodel.Term( gloss.language, gloss.value )
				);
			} catch ( e ) {
				// ignore
			}
		} );

		return result;
	}

	/**
	 * @param {wikibase.datamodel.TermMap} glosses
	 * @return {Array}
	 */
	function termMapToArray( glosses ) {
		var result = [];

		glosses.each( function ( language, term ) {
			result.push( { language: term.getLanguageCode(), value: term.getText() } );
		} );

		return result;
	}

}( jQuery, mediaWiki, wikibase ) );
