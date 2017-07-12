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
	 * @license GPL-2.0+
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
			if ( sense instanceof wikibase.lexeme.datamodel.Sense ) {
				this.option( 'value', sense );
				return;
			}

			if ( !this.isInEditMode() ) {
				return this.options.value;
			}

			return new wikibase.lexeme.datamodel.Sense(
				this.options.value ? this.options.value.getId() : null,
				convertGlossWidgetModelToGlossHashMap( this.glossWidget.glosses )
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
				convertGlossesToGlossWidgetModel( this.value().getGlosses() ),
				function () {
					this._trigger( 'change' );
				}.bind( this )
			);
		},

		_startEditing: function () {
			this.glossWidget.edit();

			return $.Deferred().resolve().promise();
		},

		_stopEditing: function ( dropValue ) {
			this.glossWidget.stopEditing();
			if ( dropValue ) {
				this.glossWidget.glosses = convertGlossesToGlossWidgetModel(
					this.value().getGlosses()
				);
			}

			return $.Deferred().resolve().promise();
		},

		getHelpMessage: function () {
			return $.Deferred().resolve( this.options.helpMessage ).promise();
		}
	} );

	function convertGlossesToGlossWidgetModel( glosses ) {
		var result = [];
		for ( var language in glosses ) {
			if ( glosses.hasOwnProperty( language ) ) {
				result.push( { value: glosses[ language ], language: language } );
			}
		}
		return result;
	}

	function convertGlossWidgetModelToGlossHashMap( glosses ) {
		var result = {};

		glosses.forEach( function ( gloss ) {
			result[ gloss.language ] = gloss.value;
		} );

		return result;
	}

}( jQuery, mediaWiki ) );
