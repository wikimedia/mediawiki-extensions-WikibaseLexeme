'use strict';

const assert = require( 'assert' );
const SpecialVersionPage = require( '../pageobjects/specialversion.page' );

describe( 'Special:Version', function () {

	it( 'has the Wikibase Lexeme extension enabled', function () {
		SpecialVersionPage.open();
		assert( SpecialVersionPage.wikibaseLexemeExtensionLink.isVisible() );
	} );

} );
