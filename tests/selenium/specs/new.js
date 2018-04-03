'use strict';

const assert = require( 'assert' ),
	NewLexemePage = require( '../pageobjects/newlexeme.page' );

describe( 'NewLexeme:Page', () => {

	it( 'request with "createpage" right shows form', () => {
		NewLexemePage.open();

		assert.ok( NewLexemePage.showsForm() );
	} );

} );
