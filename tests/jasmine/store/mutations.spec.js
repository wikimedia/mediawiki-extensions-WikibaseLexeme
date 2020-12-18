describe( 'mutations', function () {
	var expect = require( 'unexpected' ).clone(),
		mutations = require( './../../../resources/store/mutations.js' ),
		mutationTypes = require( './../../../resources/store/mutationTypes.js' ),
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

	it( 'DERIVE_REPRESENTATION_LANGUAGE_FROM_LEMMA changes representation language correctly', function () {
		state.lexeme.forms[ 0 ].representations = [
			{ language: '', value: '' }
		];

		mutations[ mutationTypes.DERIVE_REPRESENTATION_LANGUAGE_FROM_LEMMA ]( state, {
			formIndex: 0,
			representationIndex: 0
		} );

		expect( state.lexeme.forms[ 0 ].representations[ 0 ].language, 'to equal', 'en' );
	} );

	it( 'REPLACE_ALL_REPRESENTATIONS replaces representations of correct form', function () {
		state.lexeme.forms.push( {
			id: 'L42-F2',
			representations: [
				{ language: 'en', value: 'collars' },
			],
		} );

		mutations[ mutationTypes.REPLACE_ALL_REPRESENTATIONS ]( state, {
			formIndex: 1,
			representations: [
				{ language: 'en', value: 'colors' },
				{ language: 'en-gb', value: 'colours' }
			]
		} );

		expect( state.lexeme.forms[ 0 ].representations, 'to equal', [
			{ language: 'en', value: 'color' },
			{ language: 'en-gb', value: 'colour' }
		] );
		expect( state.lexeme.forms[ 1 ].representations, 'to equal', [
			{ language: 'en', value: 'colors' },
			{ language: 'en-gb', value: 'colours' }
		] );
	} );

} );
