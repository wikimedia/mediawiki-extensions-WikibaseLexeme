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

			var fakeEntityChangersFactory = {};
			$.extend( fakeEntityChangersFactory, entityChangersFactory );
			fakeEntityChangersFactory.getStatementsChanger = function () {
				var statementsChanger = entityChangersFactory.getStatementsChanger();
				var fakeStatementsChanger = {};
				$.extend( fakeStatementsChanger, statementsChanger );
				fakeStatementsChanger.save = function fakeStatementsChangerSave( statement ) {
					var guid = statement.getClaim().getGuid();
					if ( /^L\d+-[FS]\d+/.test( guid ) || /^[FS]\d+/.test( guid ) ) {
						return $.Deferred().resolve( statement ).promise();
					} else {
						return statementsChanger.save( statement );
					}
				};
				return fakeStatementsChanger;
			};

			PARENT.apply( this, [
				toolbarFactory,
				fakeEntityChangersFactory,
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
				buildLexemeFormListView: this.getLexemeFormListView.bind( this, lexeme, startEditingCallback ),
				buildSenseListView: this.getSenseListView.bind( this, lexeme, startEditingCallback ),
				value: lexeme
			}
		);
	};

	SELF.prototype.getEntityTermsView = function () {
		return null; // Don't render terms view
	};

	SELF.prototype.getLexemeFormListView = function ( lexeme, startEditingCallback ) {
		return this._getView(
			'lexemeformlistview',
			$( '.wikibase-lexeme-forms-section' ),
			{
				getListItemAdapter: this.getListItemAdapterForLexemeFormListView.bind( this, lexeme, startEditingCallback ),
				getAdder: this._getAdderWithStartEditing( startEditingCallback ),
				value: lexeme.forms
			}
		);
	};

	SELF.prototype.getSenseListView = function ( lexeme, startEditingCallback ) {
		return this._getView(
			'senselistview',
			$( '.wikibase-lexeme-senses-section' ),
			{
				getListItemAdapter: this.getListItemAdapterForSenseListView.bind( this, lexeme, startEditingCallback, function () {} ),
				value: lexeme.senses
			}
		);
	};

	var fakeModel = { // FIXME: replace with EntityChanger
		save: function ( form ) {
			var deferred = $.Deferred();
			if ( !form.getId() ) {
				form._id = Math.round( Math.random() * 100 );
			}
			deferred.resolve( form );
			return deferred.promise();
		}
	};

	SELF.prototype.getLexemeFormView = function (
		lexemeId,
		form,
		labelFormattingService,
		$dom,
		startEditingCallback,
		removeCallback
	) {
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
				fakeFormModelCreator( lexemeId ),
				removeCallback.bind( null, lexemeFormView ),
				form,
				startEditingCallback
			);

		// Empty formviews (added with the "add" button) should start in edit mode
		if ( !form ) {
			controller.startEditing().done( lexemeFormView.focus.bind( lexemeFormView ) );
		}

		return lexemeFormView;
	};

	function fakeFormModelCreator( lexemeId ) {
		return { // FIXME: replace with EntityChanger
			save: function ( form ) {
				var deferred = $.Deferred();
				if ( !form.getId() ) {
					form._id = lexemeId + '-F' + Math.round( Math.random() * 100 );
				}
				deferred.resolve( form );
				return deferred.promise();
			}
		};
	}

	SELF.prototype.getSenseView = function (
		lexemeId,
		sense,
		$dom,
		startEditingCallback,
		removeCallback
	) {
		var self = this;

		var senseView = this._getView(
			'senseview',
			$dom,
			{
				value: sense || new wb.lexeme.datamodel.Sense(),
				buildStatementGroupListView: this.getStatementGroupListView.bind(
					this,
					startEditingCallback
				)
			}
			),
			controller = this._getController(
				this._toolbarFactory.getToolbarContainer( senseView.element ),
				senseView,
				fakeSenseModelCreator( lexemeId ),
				removeCallback.bind( null, senseView ),
				sense,
				startEditingCallback
			);

		return senseView;
	};

	function fakeSenseModelCreator( lexemeId ) {
		return { // FIXME: replace with EntityChanger
			save: function ( sense ) {
				var deferred = $.Deferred();
				if ( !sense.getId() ) {
					sense._id = lexemeId + '-S' + Math.round( Math.random() * 100 );
				}
				deferred.resolve( sense );
				return deferred.promise();
			}
		};
	}

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

	SELF.prototype.getListItemAdapterForLexemeFormListView = function ( lexeme, startEditingCallback, removeCallback ) {
		var self = this;

		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.lexemeformview,
			getNewItem: function ( form, element ) {
				var $element = $( element );

				return self.getLexemeFormView(
					lexeme.getId(),
					form || null,
					new FakeLabelFormattingService( self._api, self._getExistingGrammaticalFormattedFeatures( $element ) ),
					$element,
					startEditingCallback,
					removeCallback
				);
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

	SELF.prototype.getListItemAdapterForSenseListView = function ( lexeme, startEditingCallback, removeCallback ) {
		var self = this;

		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.senseview,
			getNewItem: function ( sense, element ) {
				var $element = $( element );

				return self.getSenseView(
					lexeme.getId(),
					sense || null,
					$element,
					startEditingCallback,
					removeCallback
				);
			}
		} );
	};

	wb.lexeme.view.ControllerViewFactory = SELF;

}( mediaWiki, wikibase, jQuery ) );
