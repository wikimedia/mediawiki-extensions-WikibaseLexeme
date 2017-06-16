( function ( wb, util ) {
	'use strict';

	/**
	 * @class wikibase.lexeme.datamodel.LexemeForm
	 *
	 * @param {string} id
	 * @param {string} representation
	 * @param {string[]} grammaticalFeatures
	 * @param {wikibase.datamodel.StatementGroupSet} statementGroupSet
	 */
	var LexemeForm = util.inherit(
		'LexemeForm',
		function ( id, representation, grammaticalFeatures, statementGroupSet ) {
			statementGroupSet = statementGroupSet || new wb.datamodel.StatementGroupSet();
			this._id = id;
			this._representation = representation;
			this._grammaticalFeatures = grammaticalFeatures || [];

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
			 * @param {LexemeForm} other
			 * @return {boolean}
			 */
			equals: function ( other ) {
				if ( !( other instanceof LexemeForm ) ) {
					return false;
				}

				if ( this._grammaticalFeatures.length !== other._grammaticalFeatures.length ) {
					return false;
				}

				var hasAllGrammaticalFeatures = true;
				this._grammaticalFeatures.forEach( function ( gf ) {
					hasAllGrammaticalFeatures = hasAllGrammaticalFeatures &&
						other._grammaticalFeatures.indexOf( gf ) >= 0;
				} );

				return this.getId() === other.getId()
					&& this.getRepresentation() === other.getRepresentation()
					&& hasAllGrammaticalFeatures;
			}
		}
	);

	wb.lexeme.datamodel.LexemeForm = LexemeForm;

}( wikibase, util ) );
