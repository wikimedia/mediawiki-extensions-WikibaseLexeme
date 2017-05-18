( function ( wb, util ) {
	'use strict';

	var PARENT = wb.datamodel.Entity;

	/**
	 * @class wikibase.lexeme.datamodel.Lexeme
	 * @extends wikibase.datamodel.Entity
	 * @license GNU GPL v2+
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 * @todo Remove Fingerprint from Entity.js then remove it from here.
	 *
	 * @constructor
	 *
	 * @param {string} lexemeId
	 * @param {wikibase.datamodel.TermMap|null} [labels=new wikibase.datamodel.TermMap()]
	 * @param {wikibase.datamodel.StatementGroupSet|null} [statementGroupSet=new wikibase.datamodel.StatementGroupSet()]
	 *
	 * @throws {Error} if a required parameter is not specified properly.
	 */
	var SELF = wb.lexeme.datamodel.Lexeme = util.inherit(
		'WbDataModelLexeme',
		PARENT,
		function ( lexemeId, labels, statementGroupSet ) {
			labels = labels || new wb.datamodel.TermMap();
			statementGroupSet = statementGroupSet || new wb.datamodel.StatementGroupSet();

			if (
				typeof lexemeId !== 'string' ||
				!( labels instanceof wb.datamodel.TermMap ) ||
				!( statementGroupSet instanceof wb.datamodel.StatementGroupSet )
			) {
				throw new Error( 'Required parameter(s) missing or not defined properly' );
			}

			this._id = lexemeId;
			this._statementGroupSet = statementGroupSet;
			this._fingerprint = new wb.datamodel.Fingerprint( labels, new wb.datamodel.TermMap() );
		},
	{

		/**
		 * @property {wikibase.datamodel.StatementGroupSet}
		 * @private
		 */
		_statementGroupSet: null,

		/**
		 * @return {wikibase.datamodel.StatementGroupSet}
		 */
		getStatements: function () {
			return this._statementGroupSet;
		},

		/**
		 * @return {boolean}
		 */
		isEmpty: function () {
			return this._statementGroupSet.isEmpty() && this._fingerprint.isEmpty();
		},

		/**
		 * @param {*} lexeme
		 * @return {boolean}
		 */
		equals: function ( lexeme ) {
			return lexeme === this ||
				( lexeme instanceof SELF &&
					this._id === lexeme.getId() &&
					this._statementGroupSet.equals( lexeme.getStatements() ) &&
					this._fingerprint.equals( lexeme.getFingerprint() )
				);
		}
	} );

	/**
	 * @inheritdoc
	 * @property {string} [TYPE='lexeme']
	 * @static
	 */
	SELF.TYPE = 'lexeme';

}( wikibase, util ) );
