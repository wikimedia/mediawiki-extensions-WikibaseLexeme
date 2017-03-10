(function ( $, wb ) {
	'use strict';

	/**
	 * @param {RepoApi} repoApi
	 */
	var ItemLookup = function ( repoApi ) {
		if ( !repoApi ) {
			throw new Error( 'An instance of wikibase.api.RepoApi needs to be provided' );
		}

		this._repoApi = repoApi;
	};

	$.extend( ItemLookup.prototype, {

		/**
		 * @property {RepoApi}
		 */
		_repoApi: null,

		fetchEntity: function ( id ) {
			var deferred = $.Deferred();

			this._repoApi.getEntities( [ id ] )
				.done( function ( response ) {
					var item = response.entities && response.entities[ id ];

					if ( item ) {
						deferred.resolve( item );
					} else {
						deferred.reject();
					}
				} )
				.fail( function () {
					deferred.reject()
				} );

			return deferred;
		}
	} );

	wb.lexeme.services.ItemLookup = ItemLookup;

})( jQuery, wikibase );
