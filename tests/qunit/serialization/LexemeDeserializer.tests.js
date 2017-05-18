/**
 * @license GPL-2.0+
 * @author Jonas Kress
 */
( function ( $, wb, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.serialization.LexemeDeserializer' );

	var claimsSerialization = {
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
	};
	var serialization = {
		type: 'lexeme',
		id: 'L1',
		lemmas: {},
		lexicalCategory: 'Q2',
		language: 'Q2',
		claims: claimsSerialization
	};

	var expectedStatementGroupSet = new wb.datamodel.StatementGroupSet( [
		new wb.datamodel.StatementGroup( 'P1', new wb.datamodel.StatementList( [
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'L1$1' ) )
		] ) )
	] );
	var expectedDataModel = new wikibase.lexeme.datamodel.Lexeme(
		'L1',
		null,
		expectedStatementGroupSet
		);
	expectedDataModel.forms = [];

	QUnit.test( 'deserialize()', 2, function ( assert ) {
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

	QUnit.test( 'deserialize() deserializes forms', function ( assert ) {
		var ds = new wb.lexeme.serialization.LexemeDeserializer();

		var result = ds.deserialize( {
			type: 'lexeme',
			id: 'L1',
			forms: [ {
				id: 'F1',
				representation: 'some representation',
				grammaticalFeatures: [ 'Q1' ],
				claims: claimsSerialization
			} ]
		} );

		assert.ok( result.forms, 'Deserialized data model should contain form' );

		var form = result.forms[ 0 ];
		assert.ok(
			form instanceof wb.lexeme.datamodel.LexemeForm,
			'Data model should contain instance of LexemeForm'
		);
		assert.equal( form.getId(), 'F1', 'Data model should contain form id' );
		assert.equal(
			form.getRepresentation(),
			'some representation',
			'Data model should contain form representation'
		);
		assert.deepEqual(
			form.getGrammaticalFeatures(),
			[ 'Q1' ],
			'Data model should contain form grammatical features'
		);
		assert.ok(
			form.getStatements().equals( expectedStatementGroupSet ),
			'Data model should have statements on form'
		);
	} );

}( jQuery, wikibase, QUnit ) );
