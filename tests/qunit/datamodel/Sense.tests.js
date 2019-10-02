/**
 * @license GPL-2.0-or-later
 */
( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.datamodel.Sense' );

	var Sense = wb.lexeme.datamodel.Sense;
	var datamodel = require( 'wikibase.datamodel' );
	var TermMap = datamodel.TermMap;
	var Term = datamodel.Term;
	var someGlosses = new TermMap( { en: new Term( 'en', 'A very important gloss' ) } );

	QUnit.test( 'getId()', function ( assert ) {
		var expectedId = 'S123',
			sense = new Sense( expectedId, someGlosses );

		assert.equal( sense.getId(), expectedId );
	} );

	QUnit.test( 'getGlosses()', function ( assert ) {
		var sense = new Sense( 'S123', someGlosses );
		assert.equal( sense.getGlosses(), someGlosses );
	} );

	QUnit.test( 'equals()', function ( assert ) {
		var id = 'S123',
			glosses = someGlosses,
			sense = new Sense( id, glosses ),
			comparison = new Sense( id, glosses );

		assert.equal( sense.equals( comparison ), true );
	} );

	QUnit.test( 'not equals()', function ( assert ) {
		var id = 'S123',
			glosses = new TermMap( { en: new Term( 'en', 'A very important gloss' ) } ),
			emptyGlosses = new TermMap(),
			differentGloss = new TermMap( { en: new Term( 'en', 'another gloss' ) } ),
			anotherLanguageGlosses = new TermMap( { de: new Term( 'de', 'ein gloss' ) } ),
			sense = new Sense( id, glosses ),
			equalsDataProvider = [
				{
					comparison: new Sense( id, differentGloss ),
					message: 'same id, different gloss in the same language'
				},
				{
					comparison: new Sense( id, anotherLanguageGlosses ),
					message: 'same id, different glosses'
				},
				{
					comparison: new Sense( id, emptyGlosses ),
					message: 'same id, no glosses'
				},
				{
					comparison: new Sense( 'S234', glosses ),
					message: 'different id, same glosses'
				},
				{
					comparison: new Sense( 'S234', emptyGlosses ),
					message: 'different id, different glosses'
				},
				{
					comparison: null,
					message: 'not a Sense object'
				}
			];

		equalsDataProvider.forEach( function ( testData ) {
			assert.equal(
				sense.equals( testData.comparison ),
				false,
				testData.message
			);
		} );
	} );

}( wikibase ) );
