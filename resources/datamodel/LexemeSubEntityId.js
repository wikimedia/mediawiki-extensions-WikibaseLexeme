( function () {
	'use strict';

	/**
	 * @class LexemeSubEntityId
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
