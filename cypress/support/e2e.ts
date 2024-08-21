Cypress.Commands.add( 'visitTitle', ( args ) => {
	let title = null;
	let qs = {};
	if ( typeof args === 'string' ) {
		title = args;
	} else {
		title = args.title;
		qs = args.qs;
	}
	return cy.visit( {
		url: 'index.php',
		qs: Object.assign( { title }, qs )
	} );
} );
