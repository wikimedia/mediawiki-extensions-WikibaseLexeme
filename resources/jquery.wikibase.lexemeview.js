( function ( $ ) {
	'use strict';

	var PARENT = $.wikibase.entityview;

	var GlossWidget = require( 'wikibase.lexeme.widgets.GlossWidget' );

	/**
	 * View for displaying a Wikibase `Lexeme`.
	 * Copied from jQuery.wikibase.mediainfoview
	 * @class jQuery.wikibase.lexemeview
	 * @extends jQuery.wikibase.entityview
	 * @license GPL-2.0+
	 * @author Adrian Heine
	 *
	 * @param {Object} options
	 * @param {Function} options.buildStatementGroupListView
	 * @param {Function} options.buildLexemeFormListView
	 * @param {Function} options.buildSenseListView
	 *
	 * @constructor
	 *
	 */
	$.widget( 'wikibase.lexemeview', PARENT, {
		/**
		 * @inheritdoc
		 * @protected
		 */
		options: {
			buildStatementGroupListView: null,
			buildLexemeFormListView: null,
			buildSenseListView: null
		},

		/**
		 * @property {jQuery}
		 * @readonly
		 */
		$statements: null,

		/**
		 * @inheritdoc
		 * @protected
		 */
		_create: function () {
			this._createEntityview();

			this.$statements = $( '.wikibase-entityview-main > .wikibase-statementgrouplistview', this.element );
			if ( this.$statements.length === 0 ) {
				this.$statements = $( '<div/>' ).appendTo( this.$main );
			}

			this.options.buildLexemeFormListView();
			this.options.buildSenseListView();
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_init: function () {
			if ( !this.options.buildStatementGroupListView ||
				!this.options.buildLexemeFormListView ||
				!this.options.buildSenseListView
			) {
				throw new Error( 'Required option(s) missing' );
			}

			this._initStatements();
			PARENT.prototype._init.call( this );
		},

		/**
		 * @protected
		 */
		_initStatements: function () {
			this.options.buildStatementGroupListView( this.options.value, this.$statements );

			// This is here to be sure there is never a duplicate id:
			$( '.wikibase-statementgrouplistview' )
			.prev( '.wb-section-heading' )
			.first()
			.attr( 'id', 'claims' );
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_attachEventHandlers: function () {
			PARENT.prototype._attachEventHandlers.call( this );

			var self = this;

			this.element
			.on( [
				'statementviewafterstartediting.' + this.widgetName,
				'referenceviewafterstartediting.' + this.widgetName
			].join( ' ' ),
			function () {
				self._trigger( 'afterstartediting' );
			} );

			this.element
			.on( [
				'statementlistviewafterremove.' + this.widgetName,
				'statementviewafterstopediting.' + this.widgetName,
				'statementviewafterremove.' + this.widgetName,
				'referenceviewafterstopediting.' + this.widgetName
			].join( ' ' ),
			function ( event, dropValue ) {
				self._trigger( 'afterstopediting', null, [ dropValue ] );
			} );
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_setState: function ( state ) {
			PARENT.prototype._setState.call( this, state );

			this.$statements.data( 'statementgrouplistview' )[ state ]();
		}
	} );

}( jQuery ) );
