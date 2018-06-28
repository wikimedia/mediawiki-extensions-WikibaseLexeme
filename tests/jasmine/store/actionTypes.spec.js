describe( 'wikibase.lexeme.store.actionTypes', function () {
	var expect = require( 'unexpected' ).clone(),
		_ = require( 'lodash' ),
		actionTypes = require( 'wikibase.lexeme.store.actionTypes' );

	it( 'uses unique ids for all action types', function () {
		expect(
			_.keys( actionTypes ).length,
			'to equal',
			_.uniq( _.values( actionTypes ) ).length
		);
	} );

} );
