( function () {
	'use strict';

	var PARENT = $.wikibase.entityview;

	require( './jquery.wikibase.lexemeformlistview.js' );
	require( './jquery.wikibase.senselistview.js' );
	require( './widgets/LexemeHeader.js' );

	/**
	 * View for displaying a Wikibase `Lexeme`.
	 * Copied from jQuery.wikibase.mediainfoview
	 *
	 * @class jQuery.wikibase.lexemeview
	 * @extends jQuery.wikibase.entityview
	 * @license GPL-2.0-or-later
	 * @author Adrian Heine
	 *
	 * @param {Object} options
	 * @param {Function} options.buildStatementGroupListView
	 * @param {Function} options.buildFormListView
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
			buildFormListView: null,
			buildSenseListView: null,
			buildLexemeHeader: null
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
				this.$statements = $( '<div>' ).appendTo( this.$main );
			}

			this.options.buildFormListView();
			this.options.buildSenseListView();
			this.options.buildLexemeHeader();
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_init: function () {
			if ( !this.options.buildStatementGroupListView ||
				!this.options.buildFormListView ||
				!this.options.buildSenseListView ||
				!this.options.buildLexemeHeader
			) {
				throw new Error( 'Required option(s) missing' );
			}

			this._initStatements();
			PARENT.prototype._init.call( this );
		},

		/**
		 * Lexemes have lemmas instead of entity terms, so this should not be initialized.
		 *
		 * @protected
		 */
		_initEntityTerms: function () {
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

			var startEditingEvents = [
				'statementviewafterstartediting.' + this.widgetName,
				'referenceviewafterstartediting.' + this.widgetName
			];

			this.element
				.on(
					startEditingEvents.join( ' ' ),
					function () {
						self._trigger( 'afterstartediting' );
					}
				);

			var stopEditingEvents = [
				'statementlistviewafterremove.' + this.widgetName,
				'statementviewafterstopediting.' + this.widgetName,
				'statementviewafterremove.' + this.widgetName,
				'referenceviewafterstopediting.' + this.widgetName
			];

			this.element
				.on(
					stopEditingEvents.join( ' ' ),
					function ( event, dropValue ) {
						self._trigger( 'afterstopediting', null, [ dropValue ] );
					}
				);
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

	mw.hook( 'wikibase.lexemeview.ready' ).fire();

}() );
