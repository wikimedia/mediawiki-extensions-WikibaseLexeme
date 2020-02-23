'use strict';

const assert = require( 'assert' ),
	MergeLexemesPage = require( '../../pageobjects/specialmergelexemes.page' ),
	loginAdmin = require( '../../loginAdmin' ),
	MWApi = require( 'wdio-mediawiki/Api' );

describe( 'Special:MergeLexemes', () => {
	describe( 'when blocked', () => {
		beforeEach( () => {
			return browser.call( () => MWApi.blockUser() );
		} );

		it( 'is not possible to edit', () => {
			loginAdmin();

			MergeLexemesPage.open();

			assert.strictEqual( MergeLexemesPage.showsForm(), false );
			assert.ok( MergeLexemesPage.isUserBlockedErrorVisible() );
		} );

		afterEach( () => {
			return browser.call( () => MWApi.unblockUser() );
		} );
	} );
} );
