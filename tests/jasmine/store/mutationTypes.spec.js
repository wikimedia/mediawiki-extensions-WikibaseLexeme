describe( 'mutationTypes', function () {
	var expect = require( 'unexpected' ).clone(),
		_ = require( 'lodash' ),
		mutationTypes = require( './../../../resources/store/mutationTypes.js' );

	it( 'uses unique ids for all mutation types', function () {
		expect(
			_.keys( mutationTypes ).length,
			'to equal',
			_.uniq( _.values( mutationTypes ) ).length
		);
	} );

} );
