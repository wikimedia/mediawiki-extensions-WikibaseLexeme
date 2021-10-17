( function () {
	'use strict';

	/**
	 * https://vuex.vuejs.org/guide/mutations.html#using-constants-for-mutation-types
	 */
	module.exports = {
		ADD_REPRESENTATION: 'addRepresentation',
		REMOVE_REPRESENTATION: 'removeRepresentation',
		UPDATE_REPRESENTATION_VALUE: 'updateRepresentationValue',
		UPDATE_REPRESENTATION_LANGUAGE: 'updateRepresentationLanguage',
		DERIVE_REPRESENTATION_LANGUAGE_FROM_LEMMA: 'deriveRepresentationLanguageFromLemma',
		REPLACE_ALL_REPRESENTATIONS: 'replaceAllRepresentations'
	};

}() );
