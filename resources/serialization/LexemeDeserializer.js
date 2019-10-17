( function ( wb, util ) {
	'use strict';

	var SERIALIZER = require( 'wikibase.serialization' ),
		PARENT = SERIALIZER.Deserializer,
		Lexeme = require( '../datamodel/Lexeme.js' );

	/**
	 * @class LexemeDeserializer
	 * @extends SERIALIZER.Deserializer
	 * @license GNU GPL v2+
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbLexemeDeserializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @return {wikibase.lexeme.datamodel.Lexeme}
		 *
		 * @throws {Error} if serialization does not resolve to a serialized Lexeme.
		 */
		deserialize: function ( serialization ) {
			if ( serialization.type !== Lexeme.TYPE ) {
				throw new Error( 'Serialization does not resolve to a Lexeme' );
			}

			var statementGroupSetDeserializer = new SERIALIZER.StatementGroupSetDeserializer(),
				termMapDeserializer = new SERIALIZER.TermMapDeserializer();

			var forms = serialization.forms || [];
			var deserializedForms = forms.map( function ( form ) {
				return this.deserializeForm( form );
			}.bind( this ) );

			var senses = serialization.senses || [];
			var deserializedSenses = senses.map( function ( sense ) {
				return this.deserializeSense( sense );
			}.bind( this ) );

			var lexeme = new Lexeme(
				serialization.id,
				termMapDeserializer.deserialize( serialization.lemmas ),
				statementGroupSetDeserializer.deserialize( serialization.claims ),
				deserializedForms,
				deserializedSenses
			);

			return lexeme;
		},

		/**
		 * @param {Object} formSerialization
		 * @return {wikibase.lexeme.datamodel.Form}
		 */
		deserializeForm: function ( formSerialization ) {
			var statementGroupSetDeserializer = new SERIALIZER.StatementGroupSetDeserializer();
			var termMapDeserializer = new SERIALIZER.TermMapDeserializer();
			return new wb.lexeme.datamodel.Form(
				formSerialization.id,
				termMapDeserializer.deserialize( formSerialization.representations ),
				formSerialization.grammaticalFeatures,
				statementGroupSetDeserializer.deserialize( formSerialization.claims )
			);
		},

		deserializeSense: function ( senseSerialization ) {
			var statementGroupSetDeserializer = new SERIALIZER.StatementGroupSetDeserializer();
			var termMapDeserializer = new SERIALIZER.TermMapDeserializer();
			return new wb.lexeme.datamodel.Sense(
				senseSerialization.id,
				termMapDeserializer.deserialize( senseSerialization.glosses ),
				statementGroupSetDeserializer.deserialize( senseSerialization.claims )
			);
		}

	} );

}( wikibase, util ) );
