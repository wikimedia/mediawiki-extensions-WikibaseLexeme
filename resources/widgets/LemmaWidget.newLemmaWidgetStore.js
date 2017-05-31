module.exports = ( function () {
	'use strict';

	/**
	 * @callback wikibase.lexeme.widgets.LemmaWidget.newLemmaWidgetStore
	 * @param {wikibase.api.RepoApi} repoApi
	 * @param {Array} lemmas
	 * @param {string} entityId
	 * @param {int} baseRevId
	 */
	return function ( repoApi, lemmas, entityId, baseRevId ) {
		return {
			strict: true, //FIXME make it configurable
			state: {
				isSaving: false,
				baseRevId: baseRevId,
				lemmas: lemmas
			},
			mutations: {
				updateLemmas: function ( state, newLemmas ) {
					state.lemmas = newLemmas;
				},
				updateRevisionId: function ( state, revisionId ) {
					state.baseRevId = revisionId;
				},
				startSaving: function ( state ) {
					state.isSaving = true;
				},
				finishSaving: function ( state ) {
					state.isSaving = false;
				}
			},
			actions: {
				save: function ( context, lemmas ) {
					if ( context.state.isSaving ) {
						throw new Error( 'Already saving!' );
					}
					context.commit( 'startSaving' );
					var data = { lemmas: lemmas };

					var clear = false;
					return repoApi.editEntity(
						entityId,
						context.state.baseRevId,
						data,
						clear
					).then( function ( response ) {
						context.commit( 'updateRevisionId', response.entity.lastrevid );
						//TODO: This doesn't work correctly as soon as wbeditentity doesn't return lemmas in response
						context.commit( 'updateLemmas', response.entity.lemmas );
						context.commit( 'finishSaving' );
					} ).catch( function () {
						context.commit( 'finishSaving' );
					} );
				}
			}
		};
	};
} )();
