( function ( util ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' ),
		PARENT = datamodel.Entity;

	/**
	 * @class wikibase.lexeme.datamodel.Lexeme
	 * @extends datamodel.Entity
	 * @license GNU GPL v2+
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 *
	 * @constructor
	 *
	 * @param {string} lexemeId
	 * @param {datamodel.TermMap} lemmas
	 * @param {datamodel.StatementGroupSet|null} [statementGroupSet=new datamodel.StatementGroupSet()]
	 * @param {wikibase.lexeme.datamodel.Form[]} [forms=[]]
	 * @param {wikibase.lexeme.datamodel.Sense[]} [senses=[]]
	 *
	 * @throws {Error} if a required parameter is not specified properly.
	 */
	var SELF = util.inherit(
		'WbDataModelLexeme',
		PARENT,
		function ( lexemeId, lemmas, statementGroupSet, forms, senses ) {
			statementGroupSet = statementGroupSet || new datamodel.StatementGroupSet();
			forms = forms || [];
			senses = senses || [];

			if (
				typeof lexemeId !== 'string' ||
				!( lemmas instanceof datamodel.TermMap ) ||
				( lemmas.isEmpty() ) ||
				!( statementGroupSet instanceof datamodel.StatementGroupSet ) ||
				!( Array.isArray( forms ) ) ||
				!( Array.isArray( senses ) )
			) {
				throw new Error( 'Required parameter(s) missing or not defined properly' );
			}

			this._id = lexemeId;
			this._lemmas = lemmas;
			this._statementGroupSet = statementGroupSet;
			this._forms = forms;
			this._senses = senses;
		},
		{

			/**
			 * @property {datamodel.TermMap}
			 * @private
			 */
			_lemmas: null,

			/**
			 * @property {datamodel.StatementGroupSet}
			 * @private
			 */
			_statementGroupSet: null,

			/**
			 * @property {datamodel.Form[]}
			 * @private
			 */
			_forms: null,

			/**
			 * @property {datamodel.Sense[]}
			 * @private
			 */
			_senses: null,

			/**
			 * @return {datamodel.TermMap}
			 */
			getLemmas: function () {
				return this._lemmas;
			},

			/**
			 * @return {datamodel.StatementGroupSet}
			 */
			getStatements: function () {
				return this._statementGroupSet;
			},

			/**
			 * @return {wikibase.lexeme.datamodel.Form[]}
			 */
			getForms: function () {
				return this._forms;
			},

			/**
			 * @return {wikibase.lexeme.datamodel.Sense[]}
			 */
			getSenses: function () {
				return this._senses;
			},

			/**
			 * Get the ids of persisted (i.e. having an id) sub entities
			 *
			 * @return {string[]}
			 */
			getSubEntityIds: function () {
				return [].concat(
					this.getForms().map( function ( form ) {
						return form.getId();
					} ),
					this.getSenses().map( function ( sense ) {
						return sense.getId();
					} )
				).filter( function ( value ) {
					return typeof value === 'string';
				} );
			}
		}
	);

	/**
	 * @inheritdoc
	 * @property {string} [TYPE='lexeme']
	 * @static
	 */
	SELF.TYPE = 'lexeme';

	module.exports = SELF;

}( util ) );
