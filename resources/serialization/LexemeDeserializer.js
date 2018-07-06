( function ( wb, util ) {
	'use strict';

	var MODULE = wb.lexeme.serialization,
		SERIALIZER = wb.serialization,
		PARENT = SERIALIZER.Deserializer;

	/**
	 * @class wikibase.serialization.LexemeDeserializer
	 * @extends wikibase.serialization.Deserializer
	 * @license GNU GPL v2+
	 *
	 * @constructor
	 */
	MODULE.LexemeDeserializer = util.inherit( 'WbLexemeDeserializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @return {wikibase.lexeme.datamodel.Lexeme}
		 *
		 * @throws {Error} if serialization does not resolve to a serialized Lexeme.
		 */
		deserialize: function ( serialization ) {
			if ( serialization.type !== wb.lexeme.datamodel.Lexeme.TYPE ) {
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

			var lexeme = new wb.lexeme.datamodel.Lexeme(
				serialization.id,
				termMapDeserializer.deserialize( serialization.lemmas ),
				statementGroupSetDeserializer.deserialize( serialization.claims ),
				deserializedForms
			);

			// TODO switch to setter/constructor
			lexeme.senses = deserializedSenses;

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
			return new wb.lexeme.datamodel.Sense(
				senseSerialization.id,
				this._deserializeGlosses( senseSerialization.glosses ),
				statementGroupSetDeserializer.deserialize( senseSerialization.claims )
			);
		},

		_deserializeGlosses: function ( serializedGlosses ) {
			var glosses = {};

			for ( var lang in serializedGlosses ) {
				glosses[ serializedGlosses[ lang ][ 'language' ] ] = serializedGlosses[ lang ][ 'value' ];
			}

			return glosses;
		}

	} );

}( wikibase, util ) );
