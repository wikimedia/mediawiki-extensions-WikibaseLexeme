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

		var requestLemmas = {};
		currentLemmas.forEach( function ( lemma ) {
			requestLemmas[ lemma.language ] = lemma.copy();
		} );
		removedLemmas.forEach( function ( lemma ) {
			requestLemmas[ lemma.language ] = { language: lemma.language, remove: '' };
		} );

		return requestLemmas;
	}

	/**
	 * @param {wikibase.api.RepoApi} api
	 * @param {string} id
	 * @return {jquery.Promise}
	 */
	function formatEntityId( api, id ) {
		var deferred = $.Deferred(),
			dataValue = { value: { id: id }, type: 'wikibase-entityid' };

		api.formatValue(
			dataValue,
			{},
			'wikibase-item',
			'text/html',
			''
		).then( function ( d ) {
			deferred.resolve( d.result );
		} );

		return deferred.promise();
	}

	/**
	 * @callback wikibase.lexeme.widgets.LexemeHeader.newLemmaWidgetStore
	 * @param {wikibase.api.RepoApi} repoApi
	 * @param {wikibase.lexeme.datamodel.Lexeme} lexeme
	 * @param {int} baseRevId
	 * @param {string} languageLink HTML
	 * @param {string} lexicalCategoryLink HTML
	 */
	return function ( repoApi, lexeme, baseRevId, languageLink, lexicalCategoryLink ) {
		return {
			strict: true, // FIXME make it configurable
			state: {
				isSaving: false,
				baseRevId: baseRevId,
				id: lexeme.id,
				lemmas: lexeme.lemmas,
				language: lexeme.language,
				languageLink: languageLink,
				lexicalCategory: lexeme.lexicalCategory,
				lexicalCategoryLink: lexicalCategoryLink
			},
			mutations: {
				updateLemmas: function ( state, newLemmas ) {
					state.lemmas = newLemmas;
				},
				updateRevisionId: function ( state, revisionId ) {
					state.baseRevId = revisionId;
				},
				updateLanguage: function ( state, data ) {
					state.language = data.id;
					state.languageLink = data.link;
				},
				updateLexicalCategory: function ( state, data ) {
					state.lexicalCategory = data.id;
					state.lexicalCategoryLink = data.link;
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

					var data = {
							lemmas: getRequestLemmas( context.state.lemmas, lexeme.lemmas ),
							language: lexeme.language,
							lexicalCategory: lexeme.lexicalCategory
						},
						saveRequest = repoApi.editEntity(
							context.state.id,
							context.state.baseRevId,
							data,
							false //clear
						);

					return $.when(
						saveRequest,
						formatEntityId( repoApi, lexeme.language ),
						formatEntityId( repoApi, lexeme.lexicalCategory )
					).then( function ( response, formattedLanguage, formattedLexicalCategory ) {
						context.commit( 'updateRevisionId', response[ 0 ].entity.lastrevid );
						// TODO: Update state of lemmas, language and lexicalCategory if needed.
						// Note: API response does not contain lemma.
						context.commit( 'updateLemmas', response[ 0 ].entity.lemmas || lexeme.lemmas );
						context.commit( 'updateLanguage', { id: lexeme.language, link: formattedLanguage } );
						context.commit( 'updateLexicalCategory', { id: lexeme.lexicalCategory, link: formattedLexicalCategory } );

						context.commit( 'finishSaving' );
					} ).fail( function () {
						context.commit( 'finishSaving' );
					} );
				}
			}
		};
	};
} )();
