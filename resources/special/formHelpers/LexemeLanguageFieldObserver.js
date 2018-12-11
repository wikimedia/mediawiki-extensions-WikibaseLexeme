( function ( wb ) {
	'use strict';

	/**
	 * @param {jQuery} $lemmaLanguageField
	 * @param {ItemLookup} itemLookup
	 * @param {LanguageFromItemExtractor} languageExtractor
	 */
	var LexemeLanguageFieldObserver = function ( $lemmaLanguageField, itemLookup, languageExtractor ) {
		if ( !$lemmaLanguageField || !itemLookup || !languageExtractor ) {
			throw new Error( '$lemmaLanguageField, itemLookup, languageExtractor arguments need to be provided' );
		}

		this._$lemmaLanguageField = $lemmaLanguageField;
		this._itemLookup = itemLookup;
		this._languageExtractor = languageExtractor;
	};

	$.extend( LexemeLanguageFieldObserver.prototype, {

		/**
		 * @property {jQuery}
		 */
		_$lemmaLanguageField: null,

		/**
		 * @property {ItemLookup}
		 */
		_itemLookup: null,

		/**
		 * @property {LanguageFromItemExtractor}
		 */
		_languageExtractor: null,

		/**
		 * @param {string} languageItemId
		 * @param {boolean} clearLemmaLanguageField
		 */
		notify: function ( languageItemId, clearLemmaLanguageField ) {
			if ( !( clearLemmaLanguageField === false ) ) {
				clearLemmaLanguageField = true;
			}
			var self = this;

			this._itemLookup
				.fetchEntity( languageItemId )
				.done( function ( item ) {
					var language = self._languageExtractor.getLanguageFromItem( item );
					if ( language ) {
						self._$lemmaLanguageField.hide();
						self._$lemmaLanguageField.find( 'input' ).val( language );
					} else {
						self._$lemmaLanguageField.show();
						if ( clearLemmaLanguageField === true ) {
							self._$lemmaLanguageField.find( 'input' ).val( '' );
						}
					}
				} )
				.fail( function () {
					// TODO: do nothing?
				} );
		}

	} );

	wb.lexeme.special.formHelpers.LexemeLanguageFieldObserver = LexemeLanguageFieldObserver;

}( wikibase ) );
