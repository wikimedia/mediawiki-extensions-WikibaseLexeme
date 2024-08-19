export class MergeLexemesPage {

	private static get MERGE_LEXEME_SELECTORS(): Record<string, string> {
		return {
			FROM_ID: '#wb-mergelexemes-from-id',
			TO_ID: '#wb-mergelexemes-to-id',
			SUBMIT_BUTTON: '#wb-mergelexemes-submit',
			FIRST_HEADING: '#firstHeading'
		};
	}

	public open(): this {
		cy.visitTitle( 'Special:MergeLexemes' );
		return this;
	}

	public showsForm(): this {
		cy.get( this.constructor.MERGE_LEXEME_SELECTORS.FROM_ID );
		cy.get( this.constructor.MERGE_LEXEME_SELECTORS.TO_ID );
		cy.get( this.constructor.MERGE_LEXEME_SELECTORS.SUBMIT_BUTTON );
		return this;
	}

	public doesNotShowForm(): this {
		cy.get( this.constructor.MERGE_LEXEME_SELECTORS.FROM_ID ).should( 'not.exist' );
		cy.get( this.constructor.MERGE_LEXEME_SELECTORS.TO_ID ).should( 'not.exist' );
		cy.get( this.constructor.MERGE_LEXEME_SELECTORS.SUBMIT_BUTTON ).should( 'not.exist' );
		return this;
	}

	public userIsBlocked(): this {
		cy.get( this.constructor.MERGE_LEXEME_SELECTORS.FIRST_HEADING ).contains( 'User is blocked' );
		return this;
	}
}
