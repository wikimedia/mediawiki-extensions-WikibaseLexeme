require( './__namespace.js' );
wikibase.lexeme.widgets.buildLexemeHeader = ( function ( wb, Vuex ) {
	'use strict';

	var Vue = require( 'vue2' );
	var newLexemeHeaderStore = require( './LexemeHeader.newLexemeHeaderStore.js' );
	var newLemmaWidget = require( './LemmaWidget.newLemmaWidget.js' );
	var newLanguageAndLexicalCategoryWidget = require( './LanguageAndLexicalCategoryWidget.js' );
	var newLexemeHeader = require( './LexemeHeader.newLexemeHeader.js' );
	var Lemma = require( './../datamodel/Lemma.js' );

	Vue.use( Vuex );

	/**
	 * @param {Object} wbEntity
	 * @return {{lemmas: Lemma[], lexicalCategory: string|null, language: string|null, id: string}}
	 */
	function hydrateLexeme( wbEntity ) {
		return {
			lemmas: hydrateLemmas( wbEntity.lemmas ),
			lexicalCategory: wbEntity.lexicalCategory,
			language: wbEntity.language,
			id: wbEntity.id
		};
	}

	/**
	 * Create an array of Lemma from lemma information per wikibase entity object
	 *
	 * @param {Object} lemmaInfo
	 * @return {Lemma[]}
	 */
	function hydrateLemmas( lemmaInfo ) {
		var lemmas = [];
		// eslint-disable-next-line jquery/no-each-util
		$.each( lemmaInfo, function ( index, lemma ) {
			lemmas.push( new Lemma( lemma.value, lemma.language ) );
		} );
		return lemmas;
	}

	/**
	 * @tutorial Parameter is _not_ wikibase.lexeme.datamodel.Lexeme! See hydrateLexeme()
	 *
	 * @param {{lemmas: Lemma[], lexicalCategory: string|null, language: string|null, id: string}} lexeme
	 */
	function init( lexeme ) {
		var repoApi = new wb.api.RepoApi( new mw.Api() );

		var baseRevId = mw.config.get( 'wgCurRevisionId' );

		var store = new Vuex.Store( newLexemeHeaderStore(
			repoApi,
			lexeme,
			baseRevId,
			$( '.language-lexical-category-widget_language' ).html(),
			$( '.language-lexical-category-widget_lexical-category' ).html()
		) );

		var lemmaWidget = newLemmaWidget( '#lemma-widget-vue-template', mw.messages );
		var languageAndLexicalCategoryWidget = newLanguageAndLexicalCategoryWidget(
			'#language-and-lexical-category-widget-vue-template',
			repoApi,
			mw.messages
		);

		var header = newLexemeHeader(
			store,
			'#wb-lexeme-header',
			'#lexeme-header-widget-vue-template',
			lemmaWidget,
			languageAndLexicalCategoryWidget,
			mw.messages
		);

		header.methods.displayError = function ( error ) {
			var $saveButton = $( this.$el.querySelector( '.lemma-widget_save' ) );

			$saveButton.wbtooltip( {
				content: {
					code: error.code,
					message: error.info
				},
				permanent: true
			} );
			$saveButton.data( 'wbtooltip' ).show();
		};

		// eslint-disable-next-line no-unused-vars
		var app = new Vue( header );
	}

	return function () {
		$.Deferred( function ( deferred ) {
			mw.hook( 'wikibase.entityPage.entityLoaded' ).add( function ( wbEntity ) {
				deferred.resolve( hydrateLexeme( wbEntity ) );
			} );
		} )
			.then( init )
			.fail( function ( reason ) {
				// FIXME: Change to lexeme-extension-specific logger once defined
				mw.log.error( 'LexemeHeader could not be initialized from wikibase.entityPage.entityLoaded', reason );
			} );
	};

} )( wikibase, Vuex );
