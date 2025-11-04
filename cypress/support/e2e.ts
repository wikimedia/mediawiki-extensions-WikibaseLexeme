Cypress.Commands.add( 'visitTitle', ( args, qsDefaults = {} ) => {
	let options = null;
	let title = null;
	if ( typeof args === 'string' ) {
		title = args;
		options = {
			qs: Object.assign( qsDefaults, {
				title: args
			} )
		};
	} else {
		options = args;
		title = options.title;
		if ( options.qs !== undefined ) {
			options.qs = Object.assign( qsDefaults, options.qs, { title } );
		} else {
			options.qs = Object.assign( qsDefaults, {
				title
			} );
		}
	}
	return cy.visit( Object.assign( options, { url: 'index.php' } ) );
} );

Cypress.Commands.add(
	'visitTitleMobile',
	( args ) => cy.visitTitle( args, { mobileaction: 'toggle_view_mobile' } )
);

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
			visitTitle( args: string|object, qsDefaults: object ): Chainable<Window>;
			visitTitleMobile( args: string|object ): Chainable<void>;
		}
	}
}
