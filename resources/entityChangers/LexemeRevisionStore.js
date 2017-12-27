/**
 * @license GPL-2.0+
 */
( function ( wb, $ ) {
	'use strict';

	var SELF = wb.lexeme.RevisionStore = function WbLexemeRevisionStore( baseStore ) {
		this.baseStore = baseStore;
		this.formRevisions = {};
		this.formStatementRevisions = {};
	};

	$.extend( SELF.prototype, {
		/**
		 * @param {string} claimGuid
		 * @return {number}
		 */
		getClaimRevision: function ( claimGuid ) {
			var formId = this.getFormIdFromStatementId( claimGuid );

			if ( formId === null ) {
				this.baseStore.getClaimRevision( claimGuid );
			}
			if ( this.formStatementRevisions.hasOwnProperty( claimGuid ) ) {
				return this.formStatementRevisions[ claimGuid ];
			}

			return this.getFormRevision( formId );
		},

		/**
		 * @param {number} rev
		 * @param {string} claimGuid
		 */
		setClaimRevision: function ( rev, claimGuid ) {
			var formId = this.getFormIdFromStatementId( claimGuid );

			if ( formId !== null ) {
				this.formStatementRevisions[ claimGuid ] = rev;
				return;
			}

			this.baseStore.setClaimRevision( rev, claimGuid );
		},

		/**
		 * @private
		 * @param {string} statementGuid
		 * @return {number|null}
		 */
		getFormIdFromStatementId: function ( statementGuid ) {
			var matchResult = statementGuid.match( /^(L\d+-F\d+)\$/ );

			if ( matchResult !== null ) {
				return matchResult[ 1 ];
			}

			return null;
		},

		/**
		 * @return {number}
		 */
		getBaseRevision: function () {
			return this.baseStore.getBaseRevision();
		},

		/**
		 * @param {number} revision
		 * @param {string} formId
		 */
		setFormRevision: function ( revision, formId ) {
			this.formRevisions[ formId ] = revision;
		},

		/**
		 * @param {string} formId
		 * @return {number}
		 */
		getFormRevision: function ( formId ) {
			if ( this.formRevisions.hasOwnProperty( formId ) ) {
				return this.formRevisions[ formId ];
			}

			return this.baseStore.getBaseRevision();
		}

	} );

}( wikibase, jQuery ) );
