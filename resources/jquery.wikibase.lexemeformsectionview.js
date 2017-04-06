( function ( $ ) {
	'use strict';

	var PARENT = $.Widget;

	/**
	 * @class jQuery.wikibase.lexemeformsectionview
	 * @extends jQuery.ui.Widget
	 * @license GPL-2.0+
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {wikibase.datamodel.Forms} options.value
	 */
	$.widget( 'wikibase.lexemeformsectionview', PARENT, {
		/**
		 * @inheritdoc
		 * @protected
		 */
		options: {
			value: null //list of forms?
		},

		/**
		 * @inheritdoc
		 */
		_create: function () {
			this._attachEventHandlers();

			var listItemAdapter = new $.wikibase.listview.ListItemAdapter({
				listItemWidget: $.wikibase.lexemeformview,
				getNewItem: function ( arg1, element ) {
					return $.wikibase.lexemeformview( {}, $( element ) )
				}
			});
			var listview = new $.wikibase.listview(
				{
					listItemAdapter: listItemAdapter,
					listItemNodeName: 'h3'
				},
				$( '.wikibase-lexeme-forms' )
			);

			var $toolbarContainer = $( '<div class="wikibase-addtoolbar-container wikibase-toolbar-container"></div>' );
			this.element.append( $toolbarContainer );

			new $.wikibase.addtoolbar( {
				add: function () {
					listview.enterNewItem();
				}
			}, $toolbarContainer )
		},

		/**
		 * @protected
		 */
		_attachEventHandlers: function () {
			var element = this.element;
			element.on( 'click.' + this.widgetName, '.wb-section-heading', function () {
				element.find( '.wikibase-lexeme-forms' ).toggle();
			} );
		}

	} );
}( jQuery ) );
