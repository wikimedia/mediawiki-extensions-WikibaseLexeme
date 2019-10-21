/**
 * @license GPL-2.0-or-later
 * @author Jonas Kress
 */
( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.serialization.LexemeDeserializer' );

	var datamodel = require( 'wikibase.datamodel' ),
		TermMap = datamodel.TermMap,
		Term = datamodel.Term,
		LexemeDeserializer = require( '../../../resources/serialization/LexemeDeserializer.js' ),
		Lexeme = require( '../../../resources/datamodel/Lexeme.js' );

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
		lemmas: {
			de: {
				language: 'de',
				value: 'blah'
			}
		},
		lexicalCategory: 'Q2',
		language: 'Q2',
		claims: claimsSerialization,
		forms: [],
		senses: []
	};

	var expectedStatementGroupSet = new datamodel.StatementGroupSet( [
		new datamodel.StatementGroup( 'P1', new datamodel.StatementList( [
			new datamodel.Statement( new datamodel.Claim(
				new datamodel.PropertyNoValueSnak( 'P1' ), null, 'L1$1' ) )
		] ) )
	] );
	var expectedDataModel = new Lexeme(
		'L1',
		new datamodel.TermMap( { de: new datamodel.Term( 'de', 'blah' ) } ),
		expectedStatementGroupSet,
		[],
		[]
	);

	QUnit.test( 'deserialize()', function ( assert ) {
		var ds = new LexemeDeserializer(),
			lexeme = ds.deserialize( serialization );

		assert.equal( lexeme.getId(), expectedDataModel.getId() );
		assert.equal( lexeme.getType(), expectedDataModel.getType() );
		assert.deepEqual( lexeme.getLemmas(), expectedDataModel.getLemmas() );
		assert.deepEqual( lexeme.getStatements(), expectedDataModel.getStatements() );
		assert.deepEqual( lexeme.getForms(), expectedDataModel.getForms() );
		assert.deepEqual( lexeme.getSenses(), expectedDataModel.getSenses() );
	} );

	QUnit.test( 'deserialize() deserializes forms', function ( assert ) {
		var ds = new LexemeDeserializer();

		var result = ds.deserialize( {
			type: 'lexeme',
			id: 'L1',
			lemmas: {
				de: {
					language: 'de',
					value: 'foo'
				}
			},
			lexicalCategory: 'Q2',
			language: 'Q1',
			forms: [ {
				id: 'F1',
				representations: { en: { language: 'en', value: 'some representation' } },
				grammaticalFeatures: [ 'Q1' ],
				claims: claimsSerialization
			} ],
			senses: []
		} );

		assert.ok( result.getForms(), 'Deserialized data model should contain forms' );

		var form = result.getForms()[ 0 ];
		assert.ok(
			form instanceof wb.lexeme.datamodel.Form,
			'Data model should contain instance of Form'
		);
		assert.equal( form.getId(), 'F1', 'Data model should contain form id' );
		assert.ok(
			form.getRepresentations().equals( new TermMap( {
				en: new Term(
					'en',
					'some representation'
				)
			} ) ),
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

	QUnit.test(
		'deserialize() deserializes forms when `forms` key is not present',
		function ( assert ) {
			var ds = new LexemeDeserializer();

			var result = ds.deserialize( {
				type: 'lexeme',
				id: 'L1',
				lemmas: {
					de: {
						language: 'de',
						value: 'foo'
					}
				},
				lexicalCategory: 'Q2',
				language: 'Q1',
				senses: []
			} );

			assert.ok( result.getForms(), 'Deserialized data model should contain forms' );
		}
	);

	QUnit.test( 'deserialize() deserializes senses', function ( assert ) {
		var ds = new LexemeDeserializer();

		var result = ds.deserialize( {
			type: 'lexeme',
			id: 'L1',
			lemmas: {
				de: {
					language: 'de',
					value: 'foo'
				}
			},
			lexicalCategory: 'Q2',
			language: 'Q1',
			forms: [],
			senses: [ {
				id: 'L1-S1',
				glosses: { en: { language: 'en', value: 'Some English gloss' } },
				claims: claimsSerialization
			} ]
		} );

		assert.ok( result.getSenses(), 'Deserialized data model should contain senses' );

		var sense = result.getSenses()[ 0 ];
		assert.ok(
			sense instanceof wb.lexeme.datamodel.Sense,
			'Data model should contain instance of Sense'
		);
		assert.equal( sense.getId(), 'L1-S1', 'Data model should contain sense id' );
		assert.ok(
			sense.getGlosses().equals( new TermMap( {
				en: new Term(
					'en',
					'Some English gloss'
				)
			} ) ),
			'Data model should contain sense glosses'
		);
		assert.ok(
			sense.getStatements().equals( expectedStatementGroupSet ),
			'Data model should have statements on sense'
		);
	} );

	QUnit.test(
		'deserialize() deserializes senses when `senses` key is not present',
		function ( assert ) {
			var ds = new LexemeDeserializer();

			var result = ds.deserialize( {
				type: 'lexeme',
				id: 'L1',
				lemmas: {
					de: {
						language: 'de',
						value: 'foo'
					}
				},
				lexicalCategory: 'Q2',
				language: 'Q1',
				forms: []
			} );

			assert.ok( result.getSenses(), 'Deserialized data model should contain senses' );
		}
	);

}( wikibase ) );
