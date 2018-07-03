( function ( wb, util ) {
	'use strict';

	var PARENT = wb.datamodel.Entity;

	/**
	 * @class wikibase.lexeme.datamodel.Lexeme
	 * @extends wikibase.datamodel.Entity
	 * @license GNU GPL v2+
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 *
	 * @constructor
	 *
	 * @param {string} lexemeId
	 * @param {wikibase.datamodel.TermMap} lemmas
	 * @param {string} lexicalCategory
	 * @param {string} language
	 * @param {wikibase.datamodel.StatementGroupSet|null} [statementGroupSet=new wikibase.datamodel.StatementGroupSet()]
	 * @param {wikibase.lexeme.datamodel.Form[]} [forms=[]]
	 *
	 * @throws {Error} if a required parameter is not specified properly.
	 */
	var SELF = wb.lexeme.datamodel.Lexeme = util.inherit(
		'WbDataModelLexeme',
		PARENT,
		function ( lexemeId, lemmas, lexicalCategory, language, statementGroupSet, forms ) {
			statementGroupSet = statementGroupSet || new wb.datamodel.StatementGroupSet();
			forms = forms || [];

			if (
				typeof lexemeId !== 'string' ||
				!( lemmas instanceof wb.datamodel.TermMap ) ||
				( lemmas.isEmpty() ) ||
				typeof lexicalCategory !== 'string' ||
				typeof language !== 'string' ||
				!( statementGroupSet instanceof wb.datamodel.StatementGroupSet ) ||
				!( Array.isArray( forms ) )
			) {
				throw new Error( 'Required parameter(s) missing or not defined properly' );
			}

			this._id = lexemeId;
			this._lemmas = lemmas;
			this._lexicalCategory = lexicalCategory;
			this._language = language;
			this._statementGroupSet = statementGroupSet;
			this._forms = forms;
		},
		{

			/**
			 * @property {wikibase.datamodel.TermMap}
			 * @private
			 */
			_lemmas: null,

			/**
			 * @property {string}
			 * @private
			 */
			_lexicalCategory: null,

			/**
			 * @property {string}
			 * @private
			 */
			_language: null,

			/**
			 * @property {wikibase.datamodel.StatementGroupSet}
			 * @private
			 */
			_statementGroupSet: null,

			/**
			 * @property {wikibase.datamodel.Form[]}
			 * @private
			 */
			_forms: null,

			/**
			 * @return {wikibase.datamodel.TermMap}
			 */
			getLemmas: function () {
				return this._lemmas;
			},

			/**
			 * @return {string}
			 */
			getLexicalCategory: function () {
				return this._lexicalCategory;
			},

			/**
			 * @return {string}
			 */
			getLanguage: function () {
				return this._language;
			},

			/**
			 * @return {wikibase.datamodel.StatementGroupSet}
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
			 * @return {boolean}
			 */
			isEmpty: function () {
				return this._lemmas.isEmpty() &&
					this._statementGroupSet.isEmpty() &&
					this._forms.length === 0;
			},

			/**
			 * @param {*} lexeme
			 * @return {boolean}
			 */
			equals: function ( lexeme ) {
				if ( this._forms.length !== lexeme._forms.length ) {
					return false;
				}

				for ( var i = 0; i < this._forms.length; i++ ) {
					if ( !this._forms[ i ].equals( lexeme._forms[ i ] ) ) {
						return false;
					}
				}

				return lexeme === this ||
					( lexeme instanceof SELF &&
						this._id === lexeme.getId() &&
						this._lemmas.equals( lexeme.getLemmas() ) &&
						this._statementGroupSet.equals( lexeme.getStatements() ) &&
						this._lexicalCategory === lexeme.getLexicalCategory() &&
						this._language === lexeme.getLanguage()
					);
			}
		}
	);

	/**
	 * @inheritdoc
	 * @property {string} [TYPE='lexeme']
	 * @static
	 */
	SELF.TYPE = 'lexeme';

}( wikibase, util ) );
