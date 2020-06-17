( function () {
	'use strict';

	var PARENT = $.Widget;

	require( './jquery.wikibase.lexemeformview.js' );

	/**
	 * @class jQuery.wikibase.lexemeformlistview
	 * @extends jQuery.ui.Widget
	 * @license GPL-2.0-or-later
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {jQuery.wikibase.listview.ListItemAdapter} options.getListItemAdapter
	 * @param {jQuery.wikibase.addtoolbar} options.getAdder
	 * @param {Function} options.getMessage
	 * @param {wikibase.lexeme.datamodel.Form[]} options.value
	 */
	$.widget( 'wikibase.lexemeformlistview', PARENT, {
		/**
		 * @inheritdoc
		 * @protected
		 */
		options: {
			getListItemAdapter: null,
			getAdder: null,
			getMessage: null,
			value: null
		},

		/**
		 * @type {jQuery.wikibase.listview}
		 * @private
		 */
		_listview: null,

		/**
		 * @inheritdoc
		 */
		_create: function () {
			if ( !this.options.getMessage && typeof this.options.getMessage !== 'function' ) {
				throw new Error( 'Required option not specified properly' );
			}

			PARENT.prototype._create.call( this );

			this._createListView();
			var addFormMessage = this.options.getMessage( 'wikibaselexeme-add-form' );
			this.options.getAdder( this.enterNewItem.bind( this ), this.element, addFormMessage );
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		destroy: function () {
			this._listview.destroy();
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * Creates the `listview` widget managing the `lexemeformview` widgets.
		 *
		 * @private
		 */
		_createListView: function () {
			// eslint-disable-next-line new-cap
			this._listview = new $.wikibase.listview( {
				listItemAdapter: this.options.getListItemAdapter( this._removeItem.bind( this ) ),
				listItemNodeName: 'div',
				value: this.options.value
			}, this.element.find( '.wikibase-lexeme-forms' ) );
		},

		/**
		 * Adds a new, pending `lexemeformview` to the `lexemeformlistview`.
		 *
		 * @see jQuery.wikibase.listview.enterNewItem
		 * @return {jQuery.Promise}
		 */
		enterNewItem: function () {
			return this._listview.enterNewItem();
		},

		/**
		 * Removes a `lexemeformview` widget.
		 *
		 * @param {jQuery.wikibase.lexemeformview} lexemeformview
		 */
		_removeItem: function ( lexemeformview ) {
			this._listview.removeItem( lexemeformview.element );
			this._trigger( 'afterremove' );
		}

	} );
}() );
