( function ( wb, util ) {
	'use strict';

	var PARENT = wb.serialization.Serializer;

	/**
	 * @class wikibase.lexeme.serialization.FormSerializer
	 * @extends wikibase.serialization.Serializer
	 * @license GNU GPL v2+
	 *
	 * @constructor
	 */
	wb.lexeme.serialization.FormSerializer = util.inherit( 'WbLexemeFormSerializer', PARENT, {
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

			var termMapSerializer = new wb.serialization.TermMapSerializer();

			return {
				id: form.getId(),
				representations: termMapSerializer.serialize( form.getRepresentations() ),
				grammaticalFeatures: form.getGrammaticalFeatures()
				// TODO: statements: form.getStatements()
			};
		}
	} );

}( wikibase, util ) );
