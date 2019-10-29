( function () {
	require( '../jquery.wikibase.lexemeview.js' );
	mw.hook( 'wikibase.entityPage.entityView.viewFactoryFactory.required' ).add( function ( entityNamespace, addPromise ) {
		if ( entityNamespace !== 'lexeme' ) {
			// not our view
			return;
		}
		if ( $.wikibase.lexemeview ) {
			// our view is already loaded
			return;
		}

		addPromise(
			$.Deferred( function ( deferred ) {
				mw.hook( 'wikibase.lexemeview.ready' ).add( function () {
					deferred.resolve();
				} );
			} )
		);
	} );
}() );
