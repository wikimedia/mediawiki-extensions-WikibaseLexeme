( function ( mw, wb, $ ) {
	'use strict';

	var PARENT = wb.view.ControllerViewFactory;

	var SELF = util.inherit(
		PARENT,
		function (
			toolbarFactory,
			entityChangersFactory,
			structureEditorFactory,
			contentLanguages,
			dataTypeStore,
			entityIdHtmlFormatter,
			entityIdPlainFormatter,
			entityStore,
			expertStore,
			formatterFactory,
			messageProvider,
			parserStore,
			userLanguages,
			vocabularyLookupApiUrl,
			commonsApiUrl
		) {
			var repoConfig = mw.config.get( 'wbRepo' ),
				repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php';
			this._api = wb.api.getLocationAgnosticMwApi( repoApiUrl );

			PARENT.apply( this, [
				toolbarFactory,
				entityChangersFactory,
				structureEditorFactory,
				contentLanguages,
				dataTypeStore,
				entityIdHtmlFormatter,
				entityIdPlainFormatter,
				entityStore,
				expertStore,
				formatterFactory,
				messageProvider,
				parserStore,
				userLanguages,
				vocabularyLookupApiUrl,
				commonsApiUrl
			] );
		}
	);

	SELF.prototype._api = null;

	SELF.prototype.getEntityView = function ( startEditingCallback, lexeme, $entityview ) {
		return this._getView(
			'lexemeview',
			$entityview,
			{
				buildEntityTermsView: this.getEntityTermsView.bind( this, startEditingCallback ),
				buildSitelinkGroupListView: this.getSitelinkGroupListView.bind( this, startEditingCallback ),
				buildStatementGroupListView: this.getStatementGroupListView.bind( this, startEditingCallback ),
				buildLexemeFormListView: this.getLexemeFormListView.bind( this, lexeme.forms, startEditingCallback ),
				value: lexeme
			}
		);
	};

	SELF.prototype.getLexemeFormListView = function ( forms, startEditingCallback ) {
		return this._getView(
			'lexemeformlistview',
			$( '.wikibase-lexeme-forms-section' ),
			{
				getListItemAdapter: this.getListItemAdapterForLexemeFormListView.bind( this, startEditingCallback ),
				getAdder: this._getAdderWithStartEditing( startEditingCallback ),
				value: forms
			}
		);
	};

	var fakeModel = { // FIXME: replace with EntityChanger
		save: function ( value ) {
			var deferred = $.Deferred();
			deferred.resolve( value );
			return deferred.promise();
		}
	};

	SELF.prototype.getLexemeFormView = function ( form, labelFormattingService, $dom, startEditingCallback, removeCallback ) {
		var self = this;

		var lexemeFormView = this._getView(
				'lexemeformview',
				$dom,
				{
					value: form || new wb.lexeme.datamodel.LexemeForm(),
					labelFormattingService: labelFormattingService,
					api: self._api,
					buildStatementGroupListView: this.getStatementGroupListView.bind(
						this,
						startEditingCallback
					)
				}
			),
			controller = this._getController(
				this._toolbarFactory.getToolbarContainer( lexemeFormView.element ),
				lexemeFormView,
				fakeModel,
				removeCallback,
				form,
				startEditingCallback
			);

		// Empty formviews (added with the "add" button) should start in edit mode
		if ( !form ) {
			controller.startEditing().done( lexemeFormView.focus.bind( lexemeFormView ) );
		}

		return lexemeFormView;
	};

	/**
	 * @class wikibase.LabelFormattingService
	 * @param api
	 * @param cachedData
	 * @constructor
	 */
	var FakeLabelFormattingService = function ( api, cachedData ) {
		this._cachedData = cachedData;
		this._api = api;
	};

	FakeLabelFormattingService.prototype.getHtml = function getHtml( entityId ) {
		var deferred = $.Deferred(),
			self = this;

		if ( this._cachedData[ entityId ] ) {
			return $.Deferred().resolve( this._cachedData[ entityId ] ).promise();
		}

		this._api.get( {
			action: 'wbformatvalue',
			datavalue: JSON.stringify( {
				value: {
					id: entityId
				},
				type: 'wikibase-entityid'
			} ), // FIXME use data value
			format: 'json',
			language: mw.config.get( 'wgUserLanguage' ),
			uselang: mw.config.get( 'wgUserLanguage' ),
			generate: 'text/html'
		} ).then( function ( data ) {
			self._cachedData[ entityId ] = data.result;
			deferred.resolve( data.result );
		} );

		return deferred.promise();
	};

	SELF.prototype.getListItemAdapterForLexemeFormListView = function ( startEditingCallback, removeCallback ) {
		var self = this,
			view,
			doRemove = function () {
				return removeCallback( view );
			};

		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.lexemeformview,
			getNewItem: function ( value, element ) {
				var $element = $( element );

				view = self.getLexemeFormView(
					value || null, // FIXME: if this is undefined instead of null, things break
					new FakeLabelFormattingService( self._api, self._getExistingGrammaticalFormattedFeatures( $element ) ),
					$element,
					startEditingCallback,
					doRemove // FIXME: This is not doing the right thing
				);

				return view;
			}
		} );
	};

	SELF.prototype._getExistingGrammaticalFormattedFeatures = function ( $element ) {
		var features = {};
		$.each( $element.find( '.wikibase-lexeme-form-grammatical-features-values > a' ), function ( i, el ) {
			features[ el.title.replace( 'Item:', '' ) ] = el.outerHTML; // TODO Find proper way to get Item ID here
		} );

		return features;
	};

	SELF.prototype._getExistingGrammaticalFeatures = function ( $element ) {
		var existingGrammaticalFeatures = $.map( $element.find( '.wikibase-lexeme-form-grammatical-features-values > a' ), function ( el ) {
			return $( el ).attr( 'title' );
		} ).filter( Boolean ).map( function ( title ) {
			return title.match( /Q\d+/ )[ 0 ];
		} );

		var deletedGrammaticalFeatures = $.map( $element.find( '.wikibase-lexeme-form-grammatical-features-values .wb-entity-undefinedinfo' ), function ( el ) {
			return el.previousSibling.nodeValue.match( /Q\d+/ )[ 0 ];
		} ).filter( Boolean );

		return existingGrammaticalFeatures.concat( deletedGrammaticalFeatures );
	};

	wb.lexeme.view.ControllerViewFactory = SELF;

}( mediaWiki, wikibase, jQuery ) );
