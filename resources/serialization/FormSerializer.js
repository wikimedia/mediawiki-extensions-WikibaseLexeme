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
			var representations = {};

			if ( !( form instanceof wikibase.lexeme.datamodel.Form ) ) {
				throw new Error( 'Not an instance of wikibase.lexeme.datamodel.Form' );
			}

			form.getRepresentations().each( function ( index, representation ) {
				var lang = representation.getLanguageCode();

				representations[ lang ] = {
					language: lang,
					representation: representation.getText()
				};
			} );

			return {
				id: form.getId(),
				representations: representations,
				grammaticalFeatures: form.getGrammaticalFeatures()
				// TODO: statements: form.getStatements()
			};
		}
	} );

}( wikibase, util ) );
