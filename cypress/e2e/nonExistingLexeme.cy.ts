import { NonExistingLexemePage } from '../support/pageObjects/NonExistingLexemePage';

const nonExistingLexemePage = new NonExistingLexemePage();

describe( 'Lexeme:non-existing', () => {

	it( 'says the entity does not exist', () => {
		nonExistingLexemePage.open();

		nonExistingLexemePage.firstHeading().should( 'have.text', 'Lexeme:L-invalid' );
		nonExistingLexemePage.noArticleText();
	} );

} );
