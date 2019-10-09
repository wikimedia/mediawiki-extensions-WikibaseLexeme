( function ( wb, util ) {
	'use strict';

	var serialization = require( 'wikibase.serialization' ),
		PARENT = serialization.Serializer;

	/**
	 * A serializer for forms.
	 * Statements are currently not serialized.
	 *
	 * @class wikibase.lexeme.serialization.FormSerializer
	 * @extends serialization.Serializer
	 * @license GNU GPL v2+
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbLexemeFormSerializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @param {wikibase.lexeme.datamodel.Form} form
		 * @return {Object}
		 */
		serialize: function ( form ) {
			if ( !( form instanceof wb.lexeme.datamodel.Form ) ) {
				throw new Error( 'Not an instance of wikibase.lexeme.datamodel.Form' );
			}

			var termMapSerializer = new serialization.TermMapSerializer();

			return {
				id: form.getId(),
				representations: termMapSerializer.serialize( form.getRepresentations() ),
				grammaticalFeatures: form.getGrammaticalFeatures()
				// TODO: statements: form.getStatements()
			};
		}
	} );

}( wikibase, util ) );
