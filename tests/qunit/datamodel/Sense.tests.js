/**
 * @license GPL-2.0-or-later
 */
( function ( $, wb, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.datamodel.Sense' );

	var Sense = wb.lexeme.datamodel.Sense;

	QUnit.test( 'getId()', function ( assert ) {
		var expectedId = 'S123',
			sense = new Sense( expectedId, {} );

		assert.equal( sense.getId(), expectedId );
	} );

	QUnit.test( 'getGlosses()', function ( assert ) {
		var expectedGlosses = { en: 'test gloss' },
			sense = new Sense( 'S123', expectedGlosses );

		assert.equal( sense.getGlosses(), expectedGlosses );
	} );

	QUnit.test( 'equals()', function ( assert ) {
		var id = 'S123',
			glosses = { en: 'a gloss' },
			sense = new Sense( id, glosses ),
			comparison = new Sense( id, glosses );

		assert.equal( sense.equals( comparison ), true );
	} );

	QUnit.test( 'not equals()', function ( assert ) {
		var id = 'S123',
			glosses = { en: 'a gloss' },
			emptyGlosses = {},
			differentGloss = { en: 'another gloss' },
			anotherLanguageGlosses = { de: 'ein Gloss' },
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

}( jQuery, wikibase, QUnit ) );
