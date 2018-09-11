( function () {
	'use strict';

	/**
	 * @class wikibase.lexeme.datamodel.LexemeSubEntityId
	 */
	module.exports = {

		/**
		 * @param {string} serialization
		 */
		getIdSuffix: function ( serialization ) {
			return serialization.split( '-' )[ 1 ];
		}

	};

} )();
