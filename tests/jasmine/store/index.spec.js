describe( 'store', function () {
	var expect = require( 'unexpected' ).clone(),
		LexemeStore = require( './../../../resources/store/index.js' );

	it( 'creates initial state', function () {
		var store = LexemeStore.create(
			[
				{ language: 'en', value: 'color' }
			],
			[
				{
					id: 'L42-F1',
					representations: [
						{ language: 'en', value: 'color' },
						{ language: 'en-gb', value: 'colour' }
					]
				}
			]
		);

		expect( store.state.lexeme.lemmas[ 0 ].value, 'to equal', 'color' );
		expect( store.state.lexeme.forms[ 0 ].id, 'to equal', 'L42-F1' );
	} );

} );
