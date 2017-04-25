( function ( wb, util ) {
	'use strict';

	/**
	 * @param {string} id
	 * @param {string} representation
	 */
	var LexemeForm = util.inherit(
		'LexemeForm',
		function ( id, representation ) {
			this._id = id;
			this._representation = representation;
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
			 * @param {LexemeForm} form
			 * @return {boolean}
			 */
			equals: function ( form ) {
				return form instanceof LexemeForm
					&& this.getId() === form.getId()
					&& this.getRepresentation() === form.getRepresentation()
			}
		} );

	wb.lexeme.datamodel.LexemeForm = LexemeForm;

}( wikibase, util ) );
