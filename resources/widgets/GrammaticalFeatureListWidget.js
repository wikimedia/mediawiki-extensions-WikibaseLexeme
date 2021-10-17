module.exports = ( function () {
	'use strict';

	/**
	 * @class GrammaticalFeatureListWidget
	 * @extends OO.ui.MenuTagMultiselectWidget
	 * @mixin OO.ui.mixin.RequestManager
	 *
	 * @param {Object} config Configuration object
	 * @cfg {mw.Api} api Api object to access 'wbsearchentities' action
	 * @cfg {string} language
	 * @cfg {wikibase.LabelFormattingService} labelFormattingService
	 *
	 * @license GPL-2.0-or-later
	 */
	var GrammaticalFeatureListWidget = function ( config ) {
		var selected = config.selected;
		config.selected = undefined;
		this._placeHolder = config.placeholder || '';
		config.placeholder = selected && selected.length > 0 ? '' : config.placeholder;
		config.input = { inputId: config.inputId };

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
		if ( !config.api || !config.labelFormattingService || !config.language ) {
			throw new Error( 'api, labelFormattingService and language need to be specified.' );
		}
		this._api = config.api;
		this._labelFormattingService = config.labelFormattingService;
		this._language = config.language;

		// TODO: This should extend mw.widgets.TitlesMultiselectWidget
		this.menu.$element.addClass( 'mw-widget-titleWidget-menu mw-widget-titleWidget-menu-withDescriptions' );

		var debounceInterval = typeof config.debounceInterval === 'number' ? config.debounceInterval : 250;
		this.input.on( 'change', OO.ui.debounce( this.updateMenu.bind( this ), debounceInterval ) );
		this.clearMenuItems();
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
		 * @property {wikibase.LabelFormattingService}
		 */
		_labelFormattingService: null,

		/**
		 * @property {string}
		 */
		_placeHolder: '',

		/**
		 * @inheritdoc OO.ui.mixin.RequestManager
		 */
		getRequest: function getRequest() {
			return this._api.get( {
				action: 'wbsearchentities',
				search: this.input.getValue(),
				format: 'json',
				errorformat: 'plaintext',
				language: this._language,
				uselang: this._language,
				type: 'item'
			} );
		},

		/**
		 * @inheritdoc OO.ui.mixin.RequestManager
		 */
		getRequestQuery: function () {
			return this.input.getValue();
		},

		/**
		 * @inheritdoc OO.ui.mixin.RequestManager
		 */
		getRequestCacheDataFromResponse: function ( response ) {
			return response || [];
		},

		/**
		 * @param {string} term
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

				this.addOptions( response.search );
				this.menu.updateItemVisibility();
				this.menu.toggle( true );
			}.bind( this ) );
		},

		addOptions: function ( menuOptions ) {
			this.menu.addItems(
				menuOptions.map( function ( item ) {
					return new mw.widgets.TitleOptionWidget( {
						data: item.id,
						label: item.label || item.id,
						url: item.url,
						description: item.description
					} );
				} )
			);
		},

		/**
		 * @private
		 */
		updateInputPlaceHolder: function () {
			this.input.$input.attr( 'placeholder', this.getItemCount() === 0 ? this._placeHolder : '' );
		},

		/**
		 * @inheritdoc OO.ui.MenuTagMultiselectWidget
		 */
		onMenuChoose: function onMenuChoose( menuItem ) {
			// To avoid displaying multiline selected boxes in the widget
			// we have to replace the label contents
			var itemId = menuItem.getData();
			var $label = $( '<span>' ).text( menuItem.getLabel() );
			this._labelFormattingService.getHtml( itemId ).then( function ( html ) {
				// FIXME: Add target="blank"
				$label.empty().append( html );
			} );
			this.addTag( itemId, $label );
			this.clearInput();
			this.clearMenuItems();
		},

		/**
		 * @protected
		 */
		onChangeTags: function () {
			this.updateInputPlaceHolder();
			GrammaticalFeatureListWidget.super.prototype.onChangeTags.call( this );

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
}() );
