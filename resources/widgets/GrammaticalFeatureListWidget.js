module.exports = ( function ( $, OO ) {
	'use strict';

	/**
	 * @class wikibase.lexeme.widgets.GrammaticalFeatureListWidget
	 * @extends OO.ui.MenuTagMultiselectWidget
	 * @mixins OO.ui.mixin.RequestManager
	 *
	 * @param {Object} [config] Configuration object
	 * @cfg {mw.Api} [api] Api object to access 'wbsearchentities' action
	 * @cfg {string} [language]
	 *
	 * @license GPL-2.0+
	 */
	var GrammaticalFeatureListWidget = function ( config ) {
		var selected = config.selected;
		config.selected = undefined;

		OO.ui.MenuTagMultiselectWidget.call( this, config );
		OO.ui.mixin.RequestManager.call( this, config );

		config.selected = selected;
		if ( typeof selected !== 'undefined' ) {
			Array.prototype.push.apply( this.allowedValues, selected.map( function ( item ) {
				if ( typeof item === 'string' ) {
					return item;
				}
				return item.data;
			} ) );
			this.setValue( selected );
		}

		if ( !config.api || !config.language ) {
			throw new Error( 'api and language need to be specified.' );
		}
		this._api = config.api;
		this._language = config.language;

		var debounceInterval = typeof config.debounceInterval === 'number' ? config.debounceInterval : 250;
		this.input.on( 'change',  OO.ui.debounce( this.updateMenu.bind( this ), debounceInterval ) );
	};

	OO.inheritClass( GrammaticalFeatureListWidget, OO.ui.MenuTagMultiselectWidget );
	OO.mixinClass( GrammaticalFeatureListWidget, OO.ui.mixin.RequestManager );

	$.extend( GrammaticalFeatureListWidget.prototype, {

		/**
		 * @property {string}
		 */
		_language: null,

		/**
		 * @property {mw.Api}
		 */
		_api: null,

		/**
		 * @protected
		 */
		getRequest: function getRequest() {
			return this._api.get( {
				action: 'wbsearchentities',
				search: this.input.getValue(),
				format: 'json',
				language: this._language,
				uselang: this._language,
				type: 'item'
			} );
		},

		/**
		 * @protected
		 */
		getRequestQuery: function () {
			return this.input.getValue();
		},

		/**
		 * @protected
		 */
		getRequestCacheDataFromResponse: function ( response ) {
			return response || [];
		},

		/**
		 * @private
		 */
		updateMenu: function updateMenu( term ) {
			if ( term === '' ) {
				this.clearMenuItems();
				return;
			}

			this.getRequestData().then( function ( response ) {
				if ( response.error ) {
					throw new Error( response.error.info );
				}

				this.clearMenuItems();

				var options = response.search.map( function ( item ) {
					var label = $( '<a>' ).text( item.label )
						.prop( 'href', item.url )
						.css( 'display', 'block' )
						.append( $( '<br/>' ).add( $( '<small>' ).text( item.description ) ) )
						.data( 'label', item.label );

					return new OO.ui.MenuOptionWidget( { data: item.id, label: label } );
				} );

				this.addOptions( options );
				this.menu.updateItemVisibility();
				this.menu.toggle( true );
			}.bind( this ) );
		},

		/**
		 * @protected
		 */
		onMenuChoose: function onMenuChoose( menuItem ) {
			// To avoid displaying multiline selected boxes in the widget
			// we have to replace the label contents
			this.addTag( menuItem.getData(), menuItem.getLabel().data( 'label' ) );
			this.clearInput();
			this.clearMenuItems();
		},

		/**
		 * @private
		 */
		clearInput: function () {
			this.input.$input.val( '' );
		},

		/**
		 * @private
		 */
		clearMenuItems: function () {
			this.menu.clearItems();
			this.menu.toggle( false );
			this.menu.updateItemVisibility();
		}

	} );

	return GrammaticalFeatureListWidget;
} )( jQuery, OO );
