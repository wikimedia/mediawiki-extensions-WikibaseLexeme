'use strict';

const assert = require( 'assert' ),
	SpecialVersionPage = require( '../pageobjects/specialversion.page' );

describe( 'Special:Version', function () {

	it( 'has the Wikibase Lexeme extension enabled', function () {
		SpecialVersionPage.open();
		assert( SpecialVersionPage.wikibaseLexemeExtensionLink.isDisplayed() );
	} );

} );
