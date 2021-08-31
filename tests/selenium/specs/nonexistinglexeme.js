'use strict';

const assert = require( 'assert' ),
	NonExistingLexemePage = require( '../pageobjects/nonexistinglexeme.page' );

describe( 'Lexeme:non-existing', () => {

	it( 'says the entity does not exist', () => {
		NonExistingLexemePage.open();

		assert.strictEqual(
			NonExistingLexemePage.firstHeading.getText(),
			'Lexeme:L-invalid'
		);

		NonExistingLexemePage.noArticleText.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
	} );

} );
