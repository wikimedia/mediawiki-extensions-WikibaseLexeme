( function ( wb, util ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	/**
	 * @class wikibase.lexeme.datamodel.Form
	 *
	 * @param {string} id
	 * @param {datamodel.TermMap} representations
	 * @param {string[]} grammaticalFeatures
	 * @param {datamodel.StatementGroupSet} statementGroupSet
	 */
	var Form = util.inherit(
		'Form',
		function ( id, representations, grammaticalFeatures, statementGroupSet ) {
			statementGroupSet = statementGroupSet || new datamodel.StatementGroupSet();
			representations = representations || new datamodel.TermMap();
			this._id = id;
			this._representations = representations;
			this._grammaticalFeatures = grammaticalFeatures || [];

			if (
				!( statementGroupSet instanceof datamodel.StatementGroupSet ) ||
				!( representations instanceof datamodel.TermMap )
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
			 * @type {datamodel.TermMap}
			 */
			_representations: null,

			/**
			 * @return {string[]}
			 */
			_grammaticalFeatures: [],

			/**
			 * @property {datamodel.StatementGroupSet}
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
			 * @return {datamodel.TermMap}
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
			 * @return {datamodel.StatementGroupSet}
			 */
			getStatements: function () {
				return this._statementGroupSet;
			},

			/**
			 * @param {wikibase.lexeme.datamodel.Form} other
			 * @return {boolean}
			 */
			equals: function ( other ) {
				if ( !( other instanceof Form ) ) {
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

	wb.lexeme.datamodel.Form = Form;

}( wikibase, util ) );
