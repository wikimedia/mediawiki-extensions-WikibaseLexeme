import { LexemePage } from '../support/pageObjects/LexemePage';

const lexemePage = new LexemePage();

describe( 'Lexeme:Lemma', () => {
	beforeEach( () => {
		cy.task( 'MwLexemeApi:CreateLexeme' ).as( 'lexemeId' );
	} );

	it(
		'can be edited multiple times, ' +
		'and lemmas with redundant languages cannot be saved.',
		() => {
			cy.getStringAlias( '@lexemeId' ).then( ( lexemeId ) => {
				lexemePage.open( lexemeId );

				// Edit for the first time and verify via the api
				lexemePage.setNthLemma( 0, 'test lemma', 'en' );
				cy.task( 'MwApi:GetEntityData', { entityId: lexemeId } )
					.should( ( lexemeObject ) => {
						expect( Object.keys( lexemeObject.lemmas ).length ).to.eq( 1 );
						expect( lexemeObject.lemmas.en.value ).to.eq( 'test lemma' );
					} );

				// Edit the same lemma a second time and verify via the api
				lexemePage.setNthLemma( 0, 'another lemma', 'en-gb' );
				cy.task( 'MwApi:GetEntityData', { entityId: lexemeId } )
					.should( ( lexemeObject ) => {
						expect( Object.keys( lexemeObject.lemmas ).length ).to.eq( 1 );
						expect( lexemeObject.lemmas[ 'en-gb' ].value ).to.eq( 'another lemma' );
					} );

				// Attempt to add a lemma with a redundant language, verify this doesn't work
				lexemePage.startHeaderEditMode();
				lexemePage.fillNthLemma( 1, 'another lemma', 'en-gb' );

				lexemePage.getHeaderSaveButton().should( 'be.disabled' );
				lexemePage.getRedundantLanguageWarning();
			} );
		}
	);
} );
