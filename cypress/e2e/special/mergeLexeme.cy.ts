import { MergeLexemesPage } from '../../support/pageObjects/MergeLexemesPage';
import { LoginPage } from '../../support/pageObjects/LoginPage';

const mergeLexemesPage = new MergeLexemesPage();
const loginPage = new LoginPage();

describe( 'Special:MergeLexemes', () => {
	it( 'shows the form', () => {
		mergeLexemesPage.open().showsForm();
	} );
	describe( 'when blocked', () => {
		before( () => {
			cy.task(
				'MwApi:CreateUser',
				{ usernamePrefix: 'mergetest' }
			).then( ( { username, password } ) => {
				cy.wrap( username ).as( 'blockedUsername' );
				loginPage.login( username, password );
			} ).then( function () {
				cy.task( 'MwApi:BlockUser', { username: this.blockedUsername } );
			} );
		} );

		it( 'is not possible to edit', () => {
			mergeLexemesPage.open().userIsBlocked().doesNotShowForm();
		} );

		after( function () {
			cy.task( 'MwApi:UnblockUser', { username: this.blockedUsername } );
		} );
	} );
} );
