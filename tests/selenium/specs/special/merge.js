'use strict';

const assert = require( 'assert' ),
	MergeLexemesPage = require( '../../pageobjects/specialmergelexemes.page' ),
	MWApi = require( 'wdio-mediawiki/Api' );

describe( 'Special:MergeLexemes', () => {
	describe( 'when blocked', () => {
		beforeEach( () => {
			return browser.call( async () => MWApi.blockUser( await MWApi.bot() ) );
		} );

		it( 'is not possible to edit', () => {
			MergeLexemesPage.open();

			assert.strictEqual( MergeLexemesPage.showsForm(), false );
			assert.ok( MergeLexemesPage.isUserBlockedErrorVisible() );
		} );

		afterEach( () => {
			return browser.call( async () => MWApi.unblockUser( await MWApi.bot() ) );
		} );
	} );
} );
