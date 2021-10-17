( function ( wb ) {
	'use strict';

	/**
	 * @see OO.ui.TextInputWidget
	 *
	 * @param {Object} [config] Must contain $valueField
	 *
	 * @license GPL-2.0-or-later
	 */
	var ItemSelectorWidget = function ( config ) {
		if ( !config.$valueField ) {
			throw new Error( '$valueField must be specified' );
		}

		if ( typeof config.$overlay === 'undefined' ) {
			config.$overlay = true;
		}

		OO.ui.TextInputWidget.call( this, config );
		OO.ui.mixin.LookupElement.call( this, config );

		this.$valueField = config.$valueField;
		this.$element.append( this.$valueField );

		// TODO: This should extend mw.widgets.TitleInputWidget
		this.lookupMenu.$element.addClass( 'mw-widget-titleWidget-menu mw-widget-titleWidget-menu-withDescriptions' );
	};

	OO.inheritClass( ItemSelectorWidget, OO.ui.TextInputWidget );
	OO.mixinClass( ItemSelectorWidget, OO.ui.mixin.LookupElement );

	$.extend( ItemSelectorWidget.prototype, {

		/**
		 * @property {string}
		 */
		_language: null,

		/**
		 * @property {string}
		 */
		_apiUrl: null,

		/**
		 * @property {number}
		 */
		_timeout: null,

		/**
		 * TODO: make this more generic
		 *
		 * @property {null|LexemeLanguageFieldObserver}
		 */
		_changeObserver: null,

		/**
		 * @property {boolean}
		 */
		_isInitialized: false,

		$valueField: null,

		/**
		 * Used to inject dependencies into the widget, since the element gets instantiated
		 * automatically from OOJS
		 *
		 * @param {Object} options containing apiUrl, language, timeout and changeObserver
		 */
		initialize: function ( options ) {
			if ( !options.apiUrl || !options.language || !options.timeout ) {
				throw new Error( 'apiUrl, language and timeout need to be specified.' );
			}

			this._language = options.language;
			this._apiUrl = options.apiUrl;
			this._timeout = options.timeout;
			this._changeObserver = options.changeObserver;

			this._isInitialized = true;

			// Notify changeObserver in case field is set from the PreInfuseDOM e.g. after reload
			if ( this._changeObserver ) {
				this._changeObserver.notify( this.$valueField.val(), false );
			}
		},

		/**
		 * @inheritdoc OO.ui.mixin.LookupElement
		 */
		getLookupRequest: function () {
			var term = this.getValue(),
				deferred = $.Deferred();

			if ( !this._isInitialized ) {
				throw new Error( 'The ItemSelectorWidget has not been properly initialized.' );
			}

			$.ajax( {
				url: this._apiUrl,
				timeout: this._timeout,
				dataType: 'json',
				data: this._getSearchApiParameters( term )
			} )
				.done( function ( response ) {
					if ( response.error ) {
						deferred.reject( response.error.info );
						return;
					}

					deferred.resolve( response.search );
				} )
				.fail( function ( jqXHR, textStatus ) {
					deferred.reject( textStatus );
				} );

			return deferred.promise( {
				abort: function () {
				}
			} );
		},

		/**
		 * @inheritdoc OO.ui.mixin.LookupElement
		 */
		getLookupCacheDataFromResponse: function ( response ) {
			return response || [];
		},

		/**
		 * @inheritdoc OO.ui.mixin.LookupElement
		 */
		getLookupMenuOptionsFromData: function ( data ) {
			var items = [],
				i;

			for ( i = 0; i < data.length; i++ ) {
				items.push( new mw.widgets.TitleOptionWidget( {
					data: data[ i ].id,
					label: data[ i ].label || data[ i ].id,
					description: data[ i ].description
				} ) );
			}

			return items;
		},

		_getSearchApiParameters: function ( term ) {
			return {
				action: 'wbsearchentities',
				search: term,
				format: 'json',
				errorformat: 'plaintext',
				language: this._language,
				uselang: this._language,
				type: 'item'
			};
		},

		/**
		 * @inheritdoc OO.ui.mixin.LookupElement
		 */
		onLookupMenuChoose: function ( item ) {
			this.setValue( item.getLabel() );
			this.$valueField.val( item.getData() );

			if ( this._changeObserver ) {
				this._changeObserver.notify( item.getData() );
			}
		},

		/**
		 * @inheritdoc OO.ui.TextInputWidget
		 */
		onChange: function () {
			if ( this.$valueField.val() !== '' ) {
				this.$valueField.val( '' );
			}
		}
	} );

	ItemSelectorWidget.static.reusePreInfuseDOM = function ( node, config ) {
		config = ItemSelectorWidget.parent.static.reusePreInfuseDOM( node, config );
		config.$valueField = $( node ).find( '.oo-ui-wikibase-item-selector-value' );
		return config;
	};

	wb.lexeme.widgets.ItemSelectorWidget = ItemSelectorWidget;

}( wikibase ) );
