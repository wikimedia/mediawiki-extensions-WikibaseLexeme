( function () {
	'use strict';

	var mutationTypes = require( 'wikibase.lexeme.store.mutationTypes' ),
		mutations = {};

	mutations[ mutationTypes.ADD_REPRESENTATION ] = function ( state, payload ) {
		state.lexeme.forms[ payload.formIndex ].representations
			.push( { language: payload.language, value: payload.value } );
	};
	mutations[ mutationTypes.REMOVE_REPRESENTATION ] = function ( state, payload ) {
		state.lexeme.forms[ payload.formIndex ].representations
			.splice( payload.representationIndex, 1 );
	};
	mutations[ mutationTypes.UPDATE_REPRESENTATION_VALUE ] = function ( state, payload ) {
		$.extend(
			state.lexeme.forms[ payload.formIndex ].representations[ payload.representationIndex ],
			{ value: payload.value }
		);
	};
	mutations[ mutationTypes.UPDATE_REPRESENTATION_LANGUAGE ] = function ( state, payload ) {
		$.extend(
			state.lexeme.forms[ payload.formIndex ].representations[ payload.representationIndex ],
			{ language: payload.language }
		);
	};
	mutations[ mutationTypes.DERIVE_REPRESENTATION_LANGUAGE_FROM_LEMMA ] = function ( state, payload ) {
		$.extend(
			state.lexeme.forms[ payload.formIndex ].representations[ payload.representationIndex ],
			{ language: state.lexeme.lemmas[ 0 ].language }
		);
	};

	module.exports = mutations;

} )();
