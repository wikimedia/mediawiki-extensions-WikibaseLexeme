( function ( $, mw, require, wb, Vue, Vuex ) {
	'use strict';

	/** @type {wikibase.lexeme.widgets.LemmaWidget.newLemmaWidgetStore} */
	var newLemmaWidgetStore = require( 'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidgetStore' );
	var newLemmaWidget = require( 'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidget' );
	var Lemma = require( 'wikibase.lexeme.datamodel.Lemma' );

	var wbEntity = JSON.parse( mw.config.get( 'wbEntity' ) );

	var lemmas = [];
	$.each( wbEntity.lemmas, function ( index, lemma ) {
		lemmas.push( new Lemma( lemma.value, lemma.language ) );
	} );

	var repoApi = new wb.api.RepoApi( new mw.Api() );

	var entityId = mw.config.get( 'wbEntityId' );
	var baseRevId = mw.config.get( 'wgCurRevisionId' );

	var store = new Vuex.Store( newLemmaWidgetStore( repoApi, lemmas, entityId, baseRevId ) );

	var app = new Vue( newLemmaWidget( store, '#lemmas-widget', '#lemma-widget-vue-template' ) );
} )( jQuery, mediaWiki, require, wikibase, Vue, Vuex );
