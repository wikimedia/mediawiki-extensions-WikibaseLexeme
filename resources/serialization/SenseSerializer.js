( function ( wb, util ) {
	'use strict';

	var PARENT = wb.serialization.Serializer;

	/**
	 * A serializer for senses.
	 * Statements are currently not serialized.
	 *
	 * @class wikibase.lexeme.serialization.SenseSerializer
	 * @extends wikibase.serialization.Serializer
	 * @license GNU GPL v2+
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbLexemeSenseSerializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @param {wikibase.lexeme.datamodel.Sense} sense
		 * @return {Object}
		 */
		serialize: function ( sense ) {
			if ( !( sense instanceof wb.lexeme.datamodel.Sense ) ) {
				throw new Error( 'Not an instance of wikibase.lexeme.datamodel.Sense' );
			}

			var termMapSerializer = new wb.serialization.TermMapSerializer();

			return {
				id: sense.getId(),
				glosses: termMapSerializer.serialize( sense.getGlosses() )
				// TODO statements
			};
		}
	} );

}( wikibase, util ) );
