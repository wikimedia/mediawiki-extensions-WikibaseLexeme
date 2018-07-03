describe( 'wikibase.lexeme.store.mutations', function () {
	var expect = require( 'unexpected' ).clone(),
		mutations = require( 'wikibase.lexeme.store.mutations' ),
		mutationTypes = require( 'wikibase.lexeme.store.mutationTypes' ),
		state;

	beforeEach( function () {
		state = {
			lexeme: {
				lemmas: [
					{ language: 'en', value: 'color' }
				],
				forms: [
					{
						id: 'L42-F1',
						representations: [
							{ language: 'en', value: 'color' },
							{ language: 'en-gb', value: 'colour' }
						]
					}
				]
			}
		};
	} );

	it( 'ADD_REPRESENTATION adds a new representation to the right form', function () {
		mutations[ mutationTypes.ADD_REPRESENTATION ]( state, {
			formIndex: 0,
			language: 'en-us',
			value: 'color'
		} );

		expect( state.lexeme.forms[ 0 ].representations.length, 'to equal', 3 );
		expect( state.lexeme.forms[ 0 ].representations[ 2 ], 'to equal', { language: 'en-us', value: 'color' } );
	} );

	it( 'REMOVE_REPRESENTATION removes representation leaving others with updated index', function () {
		mutations[ mutationTypes.REMOVE_REPRESENTATION ]( state, {
			formIndex: 0,
			representationIndex: 0
		} );

		expect( state.lexeme.forms[ 0 ].representations.length, 'to equal', 1 );
		expect( state.lexeme.forms[ 0 ].representations[ 0 ], 'to equal', { language: 'en-gb', value: 'colour' } );
	} );

	it( 'UPDATE_REPRESENTATION_VALUE changes correct representation value', function () {
		mutations[ mutationTypes.UPDATE_REPRESENTATION_VALUE ]( state, {
			formIndex: 0,
			representationIndex: 1,
			value: 'foo'
		} );

		expect( state.lexeme.forms[ 0 ].representations[ 1 ].value, 'to equal', 'foo' );
	} );

	it( 'UPDATE_REPRESENTATION_LANGUAGE changes correct representation language', function () {
		mutations[ mutationTypes.UPDATE_REPRESENTATION_LANGUAGE ]( state, {
			formIndex: 0,
			representationIndex: 1,
			language: 'nl'
		} );

		expect( state.lexeme.forms[ 0 ].representations[ 1 ].language, 'to equal', 'nl' );
	} );

} );
