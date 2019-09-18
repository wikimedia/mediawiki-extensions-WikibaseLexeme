module.exports = ( function ( wb ) {
	'use strict';

	var PARENT = wb.view.ReadModeViewFactory;

	var SELF = util.inherit( 'ReadModeViewFactory', PARENT, {} );

	SELF.prototype.getEntityTermsView = function () {
		return null; // Don't render terms view
	};

	SELF.prototype.getEntityView = function ( startEditingCallback, lexeme, $entityview ) {
		return this._getView(
			'lexemeview',
			$entityview,
			{
				buildEntityTermsView: this.getEntityTermsView.bind( this, startEditingCallback ),
				buildSitelinkGroupListView: this.getSitelinkGroupListView.bind( this, startEditingCallback ),
				buildStatementGroupListView: this.getStatementGroupListView.bind( this, startEditingCallback ),
				buildFormListView: function () {},
				buildSenseListView: function () {},
				buildLexemeHeader: function () {},
				value: lexeme
			}
		);
	};

	return SELF;

}( wikibase ) );
