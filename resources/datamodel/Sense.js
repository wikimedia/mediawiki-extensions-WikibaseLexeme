( function ( wb, util ) {
	'use strict';

	/**
	 * @class wikibase.lexeme.datamodel.Sense
	 *
	 * @param {string} id
	 * @param {string[]} glosses
	 */
	var Sense = util.inherit(
		'Sense',
		function ( id, glosses, statementGroupSet ) {
			statementGroupSet = statementGroupSet || new wb.datamodel.StatementGroupSet();
			this._id = id;
			this._glosses = glosses;

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
			 * @type {object}
			 */
			_glosses: null,

			/**
			 * @type {wikibase.datamodel.StatementGroupSet}
			 */
			_statementGroupSet: null,

			/**
			 * @return {string}
			 */
			getId: function () {
				return this._id;
			},

			/**
			 * @return {object}
			 */
			getGlosses: function () {
				return this._glosses;
			},

			/**
			 * @param language
			 * @return {string}
			 */
			getGloss: function ( language ) {
				return language in this._glosses ? this._glosses[ language ] : '';
			},

			/**
			 * @return {wikibase.datamodel.StatementGroupSet}
			 */
			getStatements: function () {
				return this._statementGroupSet;
			},

			/**
			 * @param {Sense} other
			 * @return {boolean}
			 */
			equals: function ( other ) {
				if ( !( other instanceof Sense ) ) {
					return false;
				}

				// TODO: should this also check statements?
				return this.getId() === other.getId() && this.getGlosses() === other.getGlosses();
			}
		}
	);

	wb.lexeme.datamodel.Sense = Sense;

}( wikibase, util ) );
