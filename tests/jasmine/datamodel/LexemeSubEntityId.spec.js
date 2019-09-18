describe( 'LexemeSubEntityId', function () {
	var expect = require( 'unexpected' ).clone();
	var LexemeSubEntityId = require( '../../../resources/datamodel/LexemeSubEntityId.js' );

	describe( 'getIdSuffix', function () {

		it( 'returns the Form id suffix', function () {
			expect( LexemeSubEntityId.getIdSuffix( 'L1-F123' ), 'to equal', 'F123' );
		} );

		it( 'returns the Sense id suffix', function () {
			expect( LexemeSubEntityId.getIdSuffix( 'L1-S321' ), 'to equal', 'S321' );
		} );

	} );

} );
