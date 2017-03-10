(function ( $, wb ) {
	'use strict';

	var LanguageFromItemExtractor = function ( languageCodePropertyId ) {
		if ( languageCodePropertyId === undefined ) {
			throw new Error( 'A language code property ID serialization needs to be provided' );
		}
		this._languageCodePropertyId = languageCodePropertyId;
	};

	$.extend( LanguageFromItemExtractor.prototype, {

		/**
		 * @property {string}
		 */
		_languageCodePropertyId: null,

		/**
		 * @param {Object} itemSerialization
		 *
		 * @return {string|false} ISO language code or false if there is no such ISO code statement
		 */
		getLanguageFromItem: function ( itemSerialization ) {
			return this._languageCodePropertyId
				&& itemSerialization.claims[ this._languageCodePropertyId ]
				&& itemSerialization.claims[ this._languageCodePropertyId ][ 0 ]
				&& itemSerialization.claims[ this._languageCodePropertyId ][ 0 ].mainsnak.datavalue.value;
		}
	} );

	wb.lexeme.services.LanguageFromItemExtractor = LanguageFromItemExtractor;

})( jQuery, wikibase );
