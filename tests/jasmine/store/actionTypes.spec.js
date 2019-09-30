describe( 'actionTypes', function () {
	var expect = require( 'unexpected' ).clone(),
		_ = require( 'lodash' ),
		actionTypes = require( './../../../resources/store/actionTypes.js' );

	it( 'uses unique ids for all action types', function () {
		expect(
			_.keys( actionTypes ).length,
			'to equal',
			_.uniq( _.values( actionTypes ) ).length
		);
	} );

} );
