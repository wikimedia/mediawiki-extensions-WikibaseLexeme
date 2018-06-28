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
	 * @param {wikibase.lexeme.datamodel.Form[]} [forms=[]]
	 *
	 * @throws {Error} if a required parameter is not specified properly.
	 */
	var SELF = wb.lexeme.datamodel.Lexeme = util.inherit(
		'WbDataModelLexeme',
		PARENT,
		function ( lexemeId, lemmas, forms ) {
			forms = forms || [];

			if (
				typeof lexemeId !== 'string' ||
				!( lemmas instanceof wb.datamodel.TermMap ) ||
				( lemmas.isEmpty() ) ||
				!( Array.isArray( forms ) )
			) {
				throw new Error( 'Required parameter(s) missing or not defined properly' );
			}

			this._id = lexemeId;
			this._lemmas = lemmas;
			this._forms = forms;
		},
		{

			/**
			 * @property {wikibase.datamodel.TermMap}
			 * @private
			 */
			_lemmas: null,

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
			 * @return {wikibase.lexeme.datamodel.Form[]}
			 */
			getForms: function () {
				return this._forms;
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
