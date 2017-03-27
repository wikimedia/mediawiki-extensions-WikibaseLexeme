(function ( $, mw, wb ) {
	'use strict';

	/**
	 * @see OO.ui.TextInputWidget
	 *
	 * @param {Object} [config]
	 *
	 * @license GPL-2.0+
	 */
	var LanguageLookupWidget = function ( config ) {
		OO.ui.TextInputWidget.call( this, config );
		OO.ui.mixin.LookupElement.call( this, config );
	};

	OO.inheritClass( LanguageLookupWidget, OO.ui.TextInputWidget );
	OO.mixinClass( LanguageLookupWidget, OO.ui.mixin.LookupElement );

	$.extend( LanguageLookupWidget.prototype, {

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
		 * @property {boolean}
		 */
		_isInitialized: false,

		/**
		 * Used to inject dependencies into the widget, since the element gets instantiated
		 * automatically from OOJS
		 *
		 * @param {Object} options containing apiUrl, language and timeout
		 */
		initialize: function ( options ) {
			if ( !options.apiUrl || !options.language || !options.timeout ) {
				throw new Error( 'apiUrl, language and timeout need to be specified.' );
			}

			this._language = options.language;
			this._apiUrl = options.apiUrl;
			this._timeout = options.timeout;

			this._isInitialized = true;
		},

		/**
		 * @see OO.ui.mixin.LookupElement.prototype.getLookupRequest
		 */
		getLookupRequest: function () {
			var term = this.getValue(),
				deferred = $.Deferred();

			if ( !this._isInitialized ) {
				throw new Error( 'The LanguageLookupWidget has not been properly initialized.' );
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
		 * @see OO.ui.mixin.LookupElement.prototype.getLookupCacheDataFromResponse
		 */
		getLookupCacheDataFromResponse: function ( response ) {
			return response || [];
		},

		/**
		 * @see OO.ui.mixin.LookupElement.prototype.getLookupMenuOptionsFromData
		 */
		getLookupMenuOptionsFromData: function ( data ) {
			var items = [],
				i;

			for ( i = 0; i < data.length; i++ ) {
				items.push( new OO.ui.MenuOptionWidget( {
					data: data[ i ].id,
					label: data[ i ].label
				} ) );
			}

			return items;
		},

		_getSearchApiParameters: function ( term ) {
			return {
				action: 'wbsearchentities',
				search: term,
				format: 'json',
				language: this._language,
				uselang: this._language,
				type: 'item'
			};
		}
	} );

	wb.lexeme.widgets.LanguageLookupWidget = LanguageLookupWidget;

})( jQuery, mediaWiki, wikibase );
