( function () {
	'use strict';

	var actionTypes = require( './actionTypes.js' ),
		mutationTypes = require( './mutationTypes.js' ),
		actions = {};

	actions[ actionTypes.ADD_REPRESENTATION ] = function ( store, payload ) {
		var representationCount = store.state.lexeme.forms[ payload.formIndex ].representations.length;

		store.commit( mutationTypes.ADD_REPRESENTATION, {
			formIndex: payload.formIndex,
			language: '',
			value: ''
		} );

		if (
			representationCount === 0 &&
			store.state.lexeme.lemmas.length === 1
		) {
			store.commit( mutationTypes.DERIVE_REPRESENTATION_LANGUAGE_FROM_LEMMA, {
				formIndex: payload.formIndex,
				representationIndex: 0
			} );
		}
	};
	actions[ actionTypes.REMOVE_REPRESENTATION ] = function ( store, payload ) {
		store.commit( mutationTypes.REMOVE_REPRESENTATION, payload );
	};
	actions[ actionTypes.UPDATE_REPRESENTATION_VALUE ] = function ( store, payload ) {
		store.commit( mutationTypes.UPDATE_REPRESENTATION_VALUE, payload );
	};
	actions[ actionTypes.UPDATE_REPRESENTATION_LANGUAGE ] = function ( store, payload ) {
		store.commit( mutationTypes.UPDATE_REPRESENTATION_LANGUAGE, payload );
	};
	actions[ actionTypes.REPLACE_ALL_REPRESENTATIONS ] = function ( store, payload ) {
		store.commit( mutationTypes.REPLACE_ALL_REPRESENTATIONS, payload );
	};

	module.exports = actions;

}() );
