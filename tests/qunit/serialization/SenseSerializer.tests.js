/**
 * @license GPL-2.0-or-later
 */
( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.serialization.SenseSerializer' );

	var Sense = wikibase.lexeme.datamodel.Sense,
		TermMap = wikibase.datamodel.TermMap,
		Term = wikibase.datamodel.Term,
		Serializer = wikibase.lexeme.serialization.SenseSerializer,
		testCases = {
			'Empty Sense': {
				sense: new Sense(),
				expected: { id: undefined, glosses: {} } },
			'Sense with ID': {
				sense: new Sense( '[ID]' ),
				expected: { id: '[ID]', glosses: {} } },
			'Sense with glosses': {
				sense: new Sense(
					null,
					new TermMap( {
						'[LANG1]': new Term( '[LANG1]', '[TEXT1]' ),
						'[LANG2]': new Term( '[LANG2]', '[TEXT2]' ),
						'[LANG3]': new Term( '[LANG3]', '[TEXT3]' )
					} ) ),
				expected: {
					id: null,
					glosses: {
						'[LANG1]': { language: '[LANG1]', value: '[TEXT1]' },
						'[LANG2]': { language: '[LANG2]', value: '[TEXT2]' },
						'[LANG3]': { language: '[LANG3]', value: '[TEXT3]' }
					}
				}
			}
		};

	function serialize( data ) {
		var s = new Serializer();
		return s.serialize( data );
	}

	QUnit.test( 'Serializing null', function ( assert ) {
		assert.throws(
			function () {
				serialize( null );
			},
			new Error( 'Not an instance of wikibase.lexeme.datamodel.Sense' ),
			'Should throw an errror'
		);

	} );

	QUnit.test( 'Serializing empty object', function ( assert ) {
		assert.throws(
			function () {
				serialize( {} );
			},
			new Error( 'Not an instance of wikibase.lexeme.datamodel.Sense' ),
			'Should throw an errror'
		);

	} );

	// eslint-disable-next-line jquery/no-each-util
	$.each( testCases, function ( testCase, data ) {
		QUnit.test( 'Serializing  "' + testCase + '" sense object', function ( assert ) {
			var s = serialize( data.sense );
			assert.ok( 'Should no throw an errror' );
			assert.deepEqual( s, data.expected, 'Should equal "' + testCase + '"' );
		} );

	} );

}( wikibase ) );
