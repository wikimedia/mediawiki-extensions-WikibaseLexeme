/**
 * @license GPL-2.0-or-later
 * @author Jonas Kress
 */
( function ( $, wb, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.serialization.FormSerializer' );

	var Form = wikibase.lexeme.datamodel.Form,
		TermMap = wikibase.datamodel.TermMap,
		Term = wikibase.datamodel.Term,
		Serializer = wikibase.lexeme.serialization.FormSerializer,
		testCases = {
			'Empty Form': {
				form: new Form(),
				expected: { id: undefined, representations: {}, grammaticalFeatures: [] } },
			'Form with ID': {
				form: new Form( '[ID]' ),
				expected: { id: '[ID]', representations: {}, grammaticalFeatures: [] } },
			'Form with representations': {
				form: new Form( null,
						new TermMap( {
							'[LANG1]': new Term( '[LANG1]', '[TEXT1]' ),
							'[LANG2]': new Term( '[LANG2]', '[TEXT2]' ),
							'[LANG3]': new Term( '[LANG3]', '[TEXT3]' )
						} ) ),
				expected: {
					id: null,
					representations: {
						'[LANG1]': { language: '[LANG1]', representation: '[TEXT1]' },
						'[LANG2]': { language: '[LANG2]', representation: '[TEXT2]' },
						'[LANG3]': { language: '[LANG3]', representation: '[TEXT3]' }
					},
					grammaticalFeatures: []
				}
			},
			'Form with grammatical features': {
				form: new Form( null, null, [ '[FEATURE1]', '[FEATURE2]', '[FEATURE3]' ] ),
				expected: { id: null, representations: {}, grammaticalFeatures: [ '[FEATURE1]', '[FEATURE2]', '[FEATURE3]' ] }
			},
			'Form with ID, representation, grammatical feature': {
				form: new Form( '[ID]', new TermMap( { '[LANG]': new Term( '[LANG]', '[TEXT]' ) } ), '[FEATURE]' ),
				expected: { id: '[ID]', representations: { '[LANG]': { language: '[LANG]', representation: '[TEXT]' } }, grammaticalFeatures: '[FEATURE]' }
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
			new Error( 'Not an instance of wikibase.lexeme.datamodel.Form' ),
			'Should throw an errror'
		);

	} );

	QUnit.test( 'Serializing empty object', function ( assert ) {
		assert.throws(
			function () {
				serialize( {} );
			},
			new Error( 'Not an instance of wikibase.lexeme.datamodel.Form' ),
			'Should throw an errror'
		);

	} );

	$.each( testCases, function ( testCase, data ) {
		QUnit.test( 'Serializing  "' + testCase + '" form object', function ( assert ) {
			var s = serialize( data.form );
			assert.ok( 'Should no throw an errror' );
			assert.deepEqual( s, data.expected, 'Should equal "' + testCase + '"' );
		} );

	} );

}( jQuery, wikibase, QUnit ) );
