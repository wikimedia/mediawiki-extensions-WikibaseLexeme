module.exports = ( function () {
	'use strict';

	var Lemma = require( 'wikibase.lexeme.datamodel.Lemma' );
	var LemmaList = require( 'wikibase.lexeme.datatransfer.LemmaList' );

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
				lemmas: new LemmaList( lexeme.lemmas ),
				language: lexeme.language,
				languageLink: languageLink,
				lexicalCategory: lexeme.lexicalCategory,
				lexicalCategoryLink: lexicalCategoryLink
			},
			mutations: {
				updateLemmas: function ( state, newLemmas ) {
					// TODO: newLemmas is array of Lemma objects when coming from lexeme.lemmas
					// but would be a generic object when passed from the API response
					state.lemmas = new LemmaList( newLemmas.map( function ( x ) {
						if ( x instanceof Lemma ) {
							return x.copy();
						}
						return new Lemma( x.value, x.language );
					} ) );
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
							lemmas: getRequestLemmas( context.state.lemmas.getLemmas(), lexeme.lemmas ),
							language: lexeme.language,
							lexicalCategory: lexeme.lexicalCategory
						},
						saveRequest = new Promise( function ( resolve, reject ) {
							repoApi.editEntity(
								context.state.id,
								context.state.baseRevId,
								data,
								false //clear
							)
								.then( resolve )
								.catch( function ( code, response ) {
									reject( response && response.error );
								} );
						} );

					return $.when(
						saveRequest,
						formatEntityId( repoApi, lexeme.language ),
						formatEntityId( repoApi, lexeme.lexicalCategory )
					).then( function ( response, formattedLanguage, formattedLexicalCategory ) {
						context.commit( 'updateRevisionId', response.entity.lastrevid );
						// TODO: Update state of lemmas, language and lexicalCategory if needed.
						// Note: API response does not contain lemma.
						context.commit( 'updateLemmas', response.entity.lemmas || lexeme.lemmas );
						context.commit( 'updateLanguage', { id: lexeme.language, link: formattedLanguage } );
						context.commit( 'updateLexicalCategory', {
							id: lexeme.lexicalCategory,
							link: formattedLexicalCategory
						} );

						context.commit( 'finishSaving' );
					} ).fail( function () {
						context.commit( 'finishSaving' );
					} );
				}
			}
		};
	};
} )();
