( function () {
	'use strict';

	var mutations = require( 'wikibase.lexeme.store.mutations' );

	function create( lemmas, forms ) {
		return new Vuex.Store( {
			strict: true,
			state: {
				lexeme: {
					lemmas: lemmas,
					forms: forms
				}
			},
			mutations: mutations
		} );
	}

	module.exports = {
		create: create
	};

} )();
