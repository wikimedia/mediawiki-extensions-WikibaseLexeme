module.exports = ( function () {
	'use strict';

	function getRequestLemmas( origLemmas, currentLemmas ) {
		var removedLemmas = [];

		origLemmas.forEach( function ( origLemma ) {
			var lemmaNotRemoved = currentLemmas.some( function ( lemma ) {
				return origLemma.language === lemma.language;
			} );
			if ( !lemmaNotRemoved ) {
				removedLemmas.push( origLemma.copy() );
			}
		} );

		var requestLemmas = [];
		currentLemmas.forEach( function ( lemma ) {
			requestLemmas.push( lemma.copy() );
		} );
		removedLemmas.forEach( function ( lemma ) {
			requestLemmas.push( { language: lemma.language, remove: '' } );
		} );

		return requestLemmas;
	}

	/**
	 * @callback wikibase.lexeme.widgets.LexemeHeader.newLemmaWidgetStore
	 * @param {wikibase.api.RepoApi} repoApi
	 * @param {wikibase.lexeme.datamodel.Lexeme} lexeme
	 * @param {int} baseRevId
	 */
	return function ( repoApi, lexeme, baseRevId ) {
		return {
			strict: true, //FIXME make it configurable
			state: {
				isSaving: false,
				baseRevId: baseRevId,
				id: lexeme.id,
				lemmas: lexeme.lemmas,
				language: lexeme.language,
				lexicalCategory: lexeme.lexicalCategory
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
				save: function ( context, lexeme ) {
					if ( context.state.isSaving ) {
						throw new Error( 'Already saving!' );
					}
					context.commit( 'startSaving' );

					var requestLemmas = getRequestLemmas( context.state.lemmas, lexeme.lemmas );

					var data = {
						lemmas: requestLemmas,
						language: lexeme.language,
						lexicalCategory: lexeme.lexicalCategory
					};

					var clear = false;
					return repoApi.editEntity(
						context.state.id,
						context.state.baseRevId,
						data,
						clear
					).then( function ( response ) {
						context.commit( 'updateRevisionId', response.entity.lastrevid );
						//TODO:  update lemmas, language and lexicalCategory once response contains the data
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
