( function () {
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
				&& itemSerialization.claims[ this._languageCodePropertyId ].length > 0
				&& this._getBestRankedLanguageCode( itemSerialization.claims[ this._languageCodePropertyId ] );
		},

		/**
		 * @param {Object[]} statements
		 */
		_getBestRankedLanguageCode: function ( statements ) {
			var RANK_ORDER = [ 'preferred', 'normal', 'deprecated' ];

			return statements.reduce( function ( currentBest, current ) {
				if ( RANK_ORDER.indexOf( current.rank ) < RANK_ORDER.indexOf( currentBest.rank ) ) {
					return current;
				}

				return currentBest;
			}, statements[ 0 ] ).mainsnak.datavalue.value;
		}
	} );

	module.exports = LanguageFromItemExtractor;

}() );
