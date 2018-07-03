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
			this._id = id;
			statementGroupSet = statementGroupSet || new wb.datamodel.StatementGroupSet();
			glosses = glosses || new wb.datamodel.TermMap();

			if (
				!( statementGroupSet instanceof wb.datamodel.StatementGroupSet ) ||
				!( glosses instanceof wb.datamodel.TermMap )
			) {
				throw new Error( 'Required parameter(s) missing or not defined properly' );
			}

			this._glosses = glosses;
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
			 * @return {wikibase.datamodel.TermMap}
			 */
			getGlosses: function () {
				return this._glosses;
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
