( function () {
	'use strict';

	var actions = require( './actions.js' ),
		mutations = require( './mutations.js' );

	function create( lemmas, forms ) {
		return new Vuex.Store( {
			strict: true,
			state: {
				lexeme: {
					lemmas: lemmas,
					forms: forms
				}
			},
			actions: actions,
			mutations: mutations
		} );
	}

	module.exports = {
		create: create
	};

} )();
