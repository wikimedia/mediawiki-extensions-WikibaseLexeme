'use strict';

const assert = require( 'assert' ),
	MergeLexemesPage = require( '../../../../tests/selenium/pageobjects/specialmergelexemes.page' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' ),
	MWApi = require( 'wdio-mediawiki/Api' );

describe( 'Special:MergeLexemes', () => {
	describe( 'when blocked', () => {
		beforeEach( () => {
			return browser.call( () => {
				return MWApi.blockUser();
			} );

		} );

		it( 'is not possible to edit', () => {
			LoginPage.loginAdmin();

			MergeLexemesPage.open();

			assert.strictEqual( MergeLexemesPage.showsForm(), false );
			assert.ok( MergeLexemesPage.isUserBlockedErrorVisible() );
		} );

		afterEach( () => {
			return browser.call( () => {
				return MWApi.unblockUser();
			} );
		} );
	} );
} );
