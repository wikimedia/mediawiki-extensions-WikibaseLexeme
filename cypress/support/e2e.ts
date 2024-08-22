Cypress.Commands.add( 'visitTitle', ( args ) => {
	let options = null;
	let title = null;
	if ( typeof args === 'string' ) {
		title = args;
		options = {
			qs: {
				title: args
			}
		};
	} else {
		options = args;
		title = options.title;
		if ( options.qs !== undefined ) {
			options.qs.title = title;
		} else {
			options.qs = {
				title
			};
		}
	}
	return cy.visit( Object.assign( options, { url: 'index.php' } ) );
} );

/**
 * Add a typed version of the 'get' command for fetching aliased <string> values
 */
Cypress.Commands.add( 'getStringAlias', ( alias: string ) => cy.get( `${ alias }` )
	.then( ( value ) => value as unknown as string )
);

/**
 * Export the type information for the new command
 */
declare global {
	// eslint-disable-next-line @typescript-eslint/no-namespace
	namespace Cypress {
		interface Chainable {
			getStringAlias( alias: string ): Cypress.Chainable<string>;
		}
	}
}
