( function ( wb, util ) {
	'use strict';

	/**
	 * @class wikibase.lexeme.datamodel.LexemeForm
	 *
	 * @param {string} id
	 * @param {wikibase.datamodel.TermMap} representations
	 * @param {string[]} grammaticalFeatures
	 * @param {wikibase.datamodel.StatementGroupSet} statementGroupSet
	 */
	var LexemeForm = util.inherit(
		'LexemeForm',
		function ( id, representations, grammaticalFeatures, statementGroupSet ) {
			statementGroupSet = statementGroupSet || new wb.datamodel.StatementGroupSet();
			representations = representations || new wb.datamodel.TermMap();
			this._id = id;
			this._representations = representations;
			this._grammaticalFeatures = grammaticalFeatures || [];

			if (
				!( statementGroupSet instanceof wb.datamodel.StatementGroupSet ) ||
				!( representations instanceof wb.datamodel.TermMap )
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
			 * @type {wikibase.datamodel.TermMap}
			 */
			_representations: null,

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
			 * @return {wikibase.datamodel.TermMap}
			 */
			getRepresentations: function () {
				return this._representations;
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
					&& this.getRepresentations().equals( other.getRepresentations() )
					&& hasAllGrammaticalFeatures;
			}
		}
	);

	wb.lexeme.datamodel.LexemeForm = LexemeForm;

}( wikibase, util ) );
