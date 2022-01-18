( function () {
	'use strict';

	var mutationTypes = require( './mutationTypes.js' ),
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
	mutations[ mutationTypes.REPLACE_ALL_REPRESENTATIONS ] = function ( state, payload ) {
		state.lexeme.forms[ payload.formIndex ].representations = payload.representations;
	};

	module.exports = mutations;

}() );
