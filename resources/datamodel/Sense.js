( function ( wb, util ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

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
			statementGroupSet = statementGroupSet || new datamodel.StatementGroupSet();
			glosses = glosses || new datamodel.TermMap();

			if (
				!( statementGroupSet instanceof datamodel.StatementGroupSet ) ||
				!( glosses instanceof datamodel.TermMap )
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
			 * @type {datamodel.TermMap}
			 */
			_glosses: null,

			/**
			 * @type {datamodel.StatementGroupSet}
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
			getGlosses: function () {
				return this._glosses;
			},

			/**
			 * @return {datamodel.StatementGroupSet}
			 */
			getStatements: function () {
				return this._statementGroupSet;
			},

			/**
			 * @param {wikibase.lexeme.datamodel.Sense} other
			 * @return {boolean}
			 */
			equals: function ( other ) {
				if ( !( other instanceof Sense ) ) {
					return false;
				}

				// TODO: should this also check statements?
				return this.getId() === other.getId() && this.getGlosses().equals( other.getGlosses() );
			}
		}
	);

	wb.lexeme.datamodel.Sense = Sense;

}( wikibase, util ) );
