( function ( wb, util ) {
	'use strict';

	/**
	 * @class wikibase.lexeme.datamodel.LexemeForm
	 *
	 * @param {string} id
	 * @param {string} representation
	 */
	var LexemeForm = util.inherit(
		'LexemeForm',
		function ( id, representation, grammaticalFeatures, statementGroupSet ) {
			statementGroupSet = statementGroupSet || new wb.datamodel.StatementGroupSet();
			this._id = id;
			this._representation = representation;
			this._grammaticalFeatures = grammaticalFeatures;

			if (
				!( statementGroupSet instanceof wb.datamodel.StatementGroupSet )
			) {
				throw new Error( 'Required parameter(s) missing or not defined properly' );
			}

			this._statementGroupSet = statementGroupSet;
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
			 * @property {wikibase.datamodel.StatementGroupSet}
			 * @private
			 */
			_statementGroupSet: null,

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
			 * @return {wikibase.datamodel.StatementGroupSet}
			 */
			getStatements: function () {
				return this._statementGroupSet;
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
