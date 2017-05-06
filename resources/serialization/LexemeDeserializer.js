( function ( wb, util ) {
	'use strict';

	var MODULE = wb.lexeme.serialization,
		SERIALIZER = wb.serialization,
		PARENT = SERIALIZER.Deserializer;

	/**
	 * @class wikibase.serialization.LexemeDeserializer
	 * @extends wikibase.serialization.Deserializer
	 * @license GNU GPL v2+
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 *
	 * @constructor
	 */
	MODULE.LexemeDeserializer = util.inherit( 'WbLexemeDeserializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @return {wikibase.datamodel.Lexeme}
		 *
		 * @throws {Error} if serialization does not resolve to a serialized Lexeme.
		 */
		deserialize: function ( serialization ) {
			if ( serialization.type !== wb.datamodel.Lexeme.TYPE ) {
				throw new Error( 'Serialization does not resolve to a Lexeme' );
			}

			var statementGroupSetDeserializer = new SERIALIZER.StatementGroupSetDeserializer(),
				termMapDeserializer = new SERIALIZER.TermMapDeserializer();

			return new wikibase.datamodel.Lexeme(
				serialization.id,
				termMapDeserializer.deserialize( serialization.labels ),
				statementGroupSetDeserializer.deserialize( serialization.claims )
			);
		}
	} );

}( wikibase, util ) );
