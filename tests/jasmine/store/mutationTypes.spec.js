describe( 'wikibase.lexeme.store.mutationTypes', function () {
	var expect = require( 'unexpected' ).clone(),
		_ = require( 'lodash' ),
		mutationTypes = require( 'wikibase.lexeme.store.mutationTypes' );

	it( 'uses unique ids for all mutation types', function () {
		expect(
			_.keys( mutationTypes ).length,
			'to equal',
			_.uniq( _.values( mutationTypes ) ).length
		);
	} );

} );
