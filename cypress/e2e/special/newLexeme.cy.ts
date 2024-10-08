import { NewLexemePage } from '../../support/pageObjects/NewLexemePage';
import { LexemePage } from '../../support/pageObjects/LexemePage';
import { Util } from 'cypress-wikibase-api';

const newLexemePage = new NewLexemePage();
const lexemePage = new LexemePage();

describe( 'NewLexeme:Page', () => {

	it( 'shows the form and creating a lexeme is possible', () => {
		const lemma = Util.getTestString( 'lemma-' ),
			language = Util.getTestString( 'language-' ),
			languageItemsLanguageCode = 'aae',
			lexicalCategory = Util.getTestString( 'lexicalCategory-' );
		newLexemePage.open();

		cy.task( 'MwApi:CreateItem', { label: language } ).then( ( languageId ) => {
			cy.task( 'MwApi:CreateItem', { label: lexicalCategory } )
				.then( ( lexicalCategoryId ) => {
					newLexemePage.createLexeme(
						lemma,
						languageId,
						lexicalCategoryId,
						languageItemsLanguageCode
					);

					lexemePage.getLemmaContainer();

					lexemePage.getHeaderId().then(
						( lexemeId ) => cy.task( 'MwApi:GetEntityData', { entityId: lexemeId } )
					).then( ( lexeme ) => {
						expect( lexeme.lemmas[ languageItemsLanguageCode ].value ).to.eq( lemma );
						expect( lexeme.language ).to.eq( languageId );
						expect( lexeme.lexicalCategory ).to.eq( lexicalCategoryId );
					} );
				} );
		} );
	} );

} );
