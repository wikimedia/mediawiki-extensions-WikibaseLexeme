( function () {
	'use strict';

	/**
	 * @class LexemeSubEntityId
	 */
	module.exports = {

		/**
		 * @param {string} serialization
		 * @return {string}
		 */
		getIdSuffix: function ( serialization ) {
			return serialization.split( '-' )[ 1 ];
		}

	};

}() );
