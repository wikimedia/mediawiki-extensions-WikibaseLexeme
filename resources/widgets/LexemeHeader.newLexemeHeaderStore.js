module.exports = ( function () {
	'use strict';

	var Lemma = require( '../datamodel/Lemma.js' );
	var LemmaList = require( '../datatransfer/LemmaList.js' );

	function getRequestLemmas( origLemmas, currentLemmas ) {
		var removedLemmas = [], origLemmaValues = {};

		origLemmas.forEach( function ( origLemma ) {
			origLemmaValues[ origLemma.language ] = origLemma.value;
			var lemmaNotRemoved = currentLemmas.some( function ( lemma ) {
				return origLemma.language === lemma.language;
			} );
			if ( !lemmaNotRemoved ) {
				removedLemmas.push( origLemma.copy() );
			}
		} );

		var requestLemmas = {};
		currentLemmas.forEach( function ( lemma ) {
			if ( lemma.value !== origLemmaValues[ lemma.language ] ) {
				requestLemmas[ lemma.language ] = lemma.copy();
			}
		} );
		removedLemmas.forEach( function ( lemma ) {
			requestLemmas[ lemma.language ] = { language: lemma.language, remove: '' };
		} );

		return requestLemmas;
	}

	/**
	 * @param {wikibase.api.RepoApi} api
	 * @param {string} id
	 * @return {jQuery.Promise}
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
	 * @param {{lemmas: Lemma[], lexicalCategory: string|null, language: string|null, id: string}} lexeme
	 *        this is NOT a wikibase.lexeme.datamodel.Lexeme!
	 * @param {number} baseRevId
	 * @param {string} languageLink HTML
	 * @param {string} lexicalCategoryLink HTML
	 * @return {Object}
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
					// newLemmas can be an array of Lemma objects when coming from lexeme.lemmas
					// or a plain object when coming from the API response
					var lemmaList = new LemmaList( [] ),
						index,
						lemma;
					for ( index in newLemmas ) {
						lemma = newLemmas[ index ];
						if ( lemma instanceof Lemma ) {
							lemmaList.add( lemma.copy() );
						} else {
							lemmaList.add( new Lemma( lemma.value, lemma.language ) );
						}
					}
					state.lemmas = lemmaList;
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
				save: function ( context, saveLexeme ) {
					if ( context.state.isSaving ) {
						throw new Error( 'Already saving!' );
					}
					context.commit( 'startSaving' );

					var data = {
							lemmas: getRequestLemmas( context.state.lemmas.getLemmas(), saveLexeme.lemmas ),
							language: saveLexeme.language,
							lexicalCategory: saveLexeme.lexicalCategory
						},
						saveRequest = $.Deferred( function ( deferred ) {
							repoApi.editEntity(
								context.state.id,
								context.state.baseRevId,
								data,
								false // clear
							)
								.then( function ( response /* , request */ ) {
									deferred.resolve( response );
								} )
								.catch( function ( code, response ) {
									var error = response && ( response.error || response.errors[ 0 ] );
									deferred.reject( error );
								} );
						} );

					return $.when(
						saveRequest,
						formatEntityId( repoApi, saveLexeme.language ),
						formatEntityId( repoApi, saveLexeme.lexicalCategory )
					).then( function ( response, formattedLanguage, formattedLexicalCategory ) {
						context.commit( 'updateRevisionId', response.entity.lastrevid );
						context.commit( 'updateLemmas', response.entity.lemmas );
						return $.when(
							response,
							// re-format entity IDs if they changed server-side
							// (but they probably didn't)
							saveLexeme.language === response.entity.language
								? formattedLanguage
								: formatEntityId( repoApi, response.entity.language ),
							saveLexeme.lexicalCategory === response.entity.lexicalCategory
								? formattedLexicalCategory
								: formatEntityId( repoApi, response.entity.lexicalCategory )
						);
					} ).then( function ( response, formattedLanguage, formattedLexicalCategory ) {
						context.commit( 'updateLanguage', {
							id: response.entity.language,
							link: formattedLanguage
						} );
						context.commit( 'updateLexicalCategory', {
							id: response.entity.lexicalCategory,
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
}() );
