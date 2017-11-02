( function ( $, mw, require, wb, Vue, Vuex ) {
	'use strict';

	/** @type {wikibase.lexeme.widgets.LexemeHeader.newLexemeHeaderStore} */
	var newLexemeHeaderStore = require( 'wikibase.lexeme.widgets.LexemeHeader.newLexemeHeaderStore' );
	var newLemmaWidget = require( 'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidget' );
	var newLanguageAndLexicalCategoryWidget = require( 'wikibase.lexeme.widgets.LanguageAndLexicalCategoryWidget' );
	var newLexemeHeader = require( 'wikibase.lexeme.widgets.LexemeHeader.newLexemeHeader' );
	var Lemma = require( 'wikibase.lexeme.datamodel.Lemma' );

	var wbEntity = JSON.parse( mw.config.get( 'wbEntity' ) );

	var lemmas = [];
	$.each( wbEntity.lemmas, function ( index, lemma ) {
		lemmas.push( new Lemma( lemma.value, lemma.language ) );
	} );

	var lexeme = {
		lemmas: lemmas,
		lexicalCategory: wbEntity.lexicalCategory,
		language: wbEntity.language,
		id: mw.config.get( 'wbEntityId' )
	};

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
	var app = new Vue( newLexemeHeader(
		store,
		'#wb-lexeme-header',
		'#lexeme-header-widget-vue-template',
		lemmaWidget,
		languageAndLexicalCategoryWidget,
		mw.messages
	) );
} )( jQuery, mediaWiki, require, wikibase, Vue, Vuex );
