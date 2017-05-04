( function ( wb, util ) {
	'use strict';

	/**
	 * @param {string} id
	 * @param {string} representation
	 */
	var LexemeForm = util.inherit(
		'LexemeForm',
		function ( id, representation, grammaticalFeatures ) {
			this._id = id;
			this._representation = representation;
			this._grammaticalFeatures = grammaticalFeatures;
		},
		{

			/**
			 * @type {string}
			 */
			_id: null,

			/**
			 * @type {string}
			 */
			_representation: null,

			/**
			 * @return {string[]}
			 */
			_grammaticalFeatures: [],

			/**
			 * @return {string}
			 */
			getId: function () {
				return this._id;
			},

			/**
			 * @return {string}
			 */
			getRepresentation: function () {
				return this._representation;
			},

			/**
			 * @return {string[]}
			 */
			getGrammaticalFeatures: function () {
				return this._grammaticalFeatures;
			},

			/**
			 * @param {LexemeForm} form
			 * @return {boolean}
			 */
			equals: function ( form ) {
				return form instanceof LexemeForm
					&& this.getId() === form.getId()
					&& this.getRepresentation() === form.getRepresentation();
			}
		} );

	wb.lexeme.datamodel.LexemeForm = LexemeForm;

}( wikibase, util ) );
