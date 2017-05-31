( function ( $, mw, require, wb, Vue, Vuex ) {
	'use strict';

	/** @type {wikibase.lexeme.widgets.LemmaWidget.newLemmaWidgetStore} */
	var newLemmaWidgetStore = require( 'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidgetStore' );

	function copyLemmaList( list ) {
		var result = [];
		list.forEach( function ( lemma ) {
			result.push( lemma.copy() );
		} );

		return result;
	}

	function Lemma( label, language ) {
		this.value = label;
		this.language = language;
	}

	Lemma.prototype.copy = function () {
		return new Lemma( this.value, this.language );
	};
	var wbEntity = JSON.parse( mw.config.get( 'wbEntity' ) );

	var lemmas = [];
	$.each( wbEntity.lemmas, function ( index, lemma ) {
		lemmas.push( new Lemma( lemma.value, lemma.language ) );
	} );

	var repoApi = new wb.api.RepoApi( new mw.Api() );

	var entityId = mw.config.get( 'wbEntityId' );
	var baseRevId = mw.config.get( 'wgCurRevisionId' );

	var store = new Vuex.Store( newLemmaWidgetStore( repoApi, lemmas, entityId, baseRevId ) );

	var app = new Vue( {
		el: '#lemmas-widget',
		template: '#lemma-widget-vue-template',
		data: {
			inEditMode: false,
			lemmas: copyLemmaList( store.state.lemmas )
		},
		computed: {
			isSaving: function () {
				return store.state.isSaving;
			}
		},
		methods: {
			edit: function () {
				this.inEditMode = true;
			},
			add: function () {
				this.lemmas.push( new Lemma( '', '' ) );
			},
			remove: function ( lemma ) {
				var index = this.lemmas.indexOf( lemma );
				this.lemmas.splice( index, 1 );
			},
			save: function () {
				store.dispatch( 'save', this.lemmas ).then( function () {
					this.inEditMode = false;
				}.bind( this ) );
			},
			cancel: function () {
				this.inEditMode = false;
				this.lemmas = copyLemmaList( store.state.lemmas );
			}
		},
		filters: {
			message: function ( key ) {
				return mw.messages.get( key );
			}
		}
	} );
} )( jQuery, mediaWiki, require, wikibase, Vue, Vuex );
