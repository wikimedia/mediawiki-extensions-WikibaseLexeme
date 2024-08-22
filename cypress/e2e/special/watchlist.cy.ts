import { SpecialWatchlistPage } from '../../support/pageObjects/SpecialWatchlistPage';
import { WatchablePage } from '../../support/pageObjects/WatchablePage';
import { LoginPage } from '../../support/pageObjects/LoginPage';

const specialWatchlistPage = new SpecialWatchlistPage();
const watchablePage = new WatchablePage();
const loginPage = new LoginPage();

describe( 'Special:Watchlist', () => {

	it( 'shows lemmas in title links to lexemes on Special:Watchlist', () => {
		cy.task( 'MwLexemeApi:CreateLexeme', { lemmas: {
			en: {
				value: 'color',
				language: 'en'
			},
			'en-gb': {
				value: 'colour',
				language: 'en-gb'
			}
		}
		} ).then( ( lexemeId ) => cy.task( 'MwApi:CreateUser', { usernamePrefix: 'watchlisttest' } )
			.then( ( { username, password } ) => ( { username, password, lexemeId } ) ) ).then( ( { username, password, lexemeId } ) => loginPage.login( username, password ).then( () => watchablePage.watch( 'Lexeme:' + lexemeId ).then( () => specialWatchlistPage.open() ).then( () => specialWatchlistPage.getTitles() ).then( ( titles ) => {
			const title = titles.first().text();

			expect( title ).to.include( 'color' );
			expect( title ).to.include( 'colour' );
			expect( title ).to.include( lexemeId );
		} ) ) );
	} );

} );
