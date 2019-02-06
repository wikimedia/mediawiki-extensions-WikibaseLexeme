( function () {
	'use strict';

	var actionTypes = require( 'wikibase.lexeme.store.actionTypes' ),
		mutationTypes = require( 'wikibase.lexeme.store.mutationTypes' ),
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
		store.commit(
			mutationTypes.UPDATE_REPRESENTATION_VALUE,
			$.extend( payload, { value: payload.value.trim() } )
		);
	};
	actions[ actionTypes.UPDATE_REPRESENTATION_LANGUAGE ] = function ( store, payload ) {
		store.commit( mutationTypes.UPDATE_REPRESENTATION_LANGUAGE, payload );
	};

	module.exports = actions;

} )();
