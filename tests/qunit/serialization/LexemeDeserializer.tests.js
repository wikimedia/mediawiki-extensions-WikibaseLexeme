/**
 * @license GPL-2.0+
 * @author Jonas Kress
 */
( function( $, wb, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.serialization.LexemeDeserializer' );

	var serialization = {
		type: 'lexeme',
		id: 'L1',
		lemmas: {},
		lexicalCategory: 'Q2',
		language: 'Q2',
		claims: {
			P1: [
					{
						id: 'L1$1',
						mainsnak: {
							snaktype: 'novalue',
							property: 'P1'
						},
						type: 'statement',
						rank: 'normal'
					}
			]
		}
	};

	var expectedDataModel = new wikibase.datamodel.Lexeme(
			'L1',
			null,
			new wb.datamodel.StatementGroupSet( [
				new wb.datamodel.StatementGroup( 'P1', new wb.datamodel.StatementList( [
					new wb.datamodel.Statement( new wb.datamodel.Claim(
							new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'L1$1' ) )
				] ) )
			] )
		);

	QUnit.test( 'deserialize()', 2, function( assert ) {
		var ds = new wb.lexeme.serialization.LexemeDeserializer();

		assert.deepEqual(
				ds.deserialize( serialization ),
				expectedDataModel,
				'Deserialized data model should deep equal expected data model'
				);

		assert.ok(
				ds.deserialize( serialization ).equals( expectedDataModel ),
				'Deserialized data model should equal expected data model'
			);
	} );

}( jQuery, wikibase, QUnit ) );
