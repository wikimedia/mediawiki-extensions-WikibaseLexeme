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
		claims: claimsSerialization,
		forms: [],
		senses: []
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
	expectedDataModel.senses = [];

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
			} ],
			senses: []
		} );

		assert.ok( result.forms, 'Deserialized data model should contain forms' );

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

	QUnit.test( 'deserialize() deserializes senses', function ( assert ) {
		var ds = new wb.lexeme.serialization.LexemeDeserializer();

		var result = ds.deserialize( {
			type: 'lexeme',
			id: 'L1',
			forms: [],
			senses: [ {
				id: 'S1',
				glosses: {
					en: { language: 'en', value: 'Some English gloss' }
				},
				claims: claimsSerialization
			} ]
		} );

		assert.ok( result.senses, 'Deserialized data model should contain senses' );

		var sense = result.senses[ 0 ];
		assert.ok(
			sense instanceof wb.lexeme.datamodel.Sense,
			'Data model should contain instance of Sense'
		);
		assert.equal( sense.getId(), 'S1', 'Data model should contain sense id' );
		assert.deepEqual(
			sense.getGlosses(),
			{ en: 'Some English gloss' },
			'Data model should contain all glosses of a sense'
		);
		assert.ok(
			sense.getStatements().equals( expectedStatementGroupSet ),
			'Data model should have statements on sense'
		);
	} );

}( jQuery, wikibase, QUnit ) );
