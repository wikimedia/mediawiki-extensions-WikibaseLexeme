( function ( wb, util ) {
	'use strict';

	var PARENT = wb.serialization.Serializer;

	/**
	 * @class wikibase.lexeme.serialization.SenseSerializer
	 * @extends wikibase.serialization.Serializer
	 * @license GNU GPL v2+
	 *
	 * @constructor
	 */
	wb.lexeme.serialization.SenseSerializer = util.inherit( 'WbLexemeSenseSerializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @param {wikibase.lexeme.datamodel.Sense} sense
		 * @return {Object}
		 */
		serialize: function ( sense ) {
			var glosses = {};

			if ( !( sense instanceof wb.lexeme.datamodel.Sense ) ) {
				throw new Error( 'Not an instance of wikibase.lexeme.datamodel.Sense' );
			}

			return {
				id: sense.getId(),
				glosses: sense.getGlosses()
			};
		}
	} );

}( wikibase, util ) );
