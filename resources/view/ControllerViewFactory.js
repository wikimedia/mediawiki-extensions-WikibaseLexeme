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

	SELF.prototype.getEntityView = function ( startEditingCallback, entity, $entityview ) {
		return this._getView(
			entity.getType() + 'view',
			$entityview,
			{
				buildEntityTermsView: this.getEntityTermsView.bind( this, startEditingCallback ),
				buildSitelinkGroupListView: this.getSitelinkGroupListView.bind( this, startEditingCallback ),
				buildStatementGroupListView: this.getStatementGroupListView.bind( this, startEditingCallback ),
				buildLexemeFormListView: this.getLexemeFormListView.bind( this, startEditingCallback ),
				value: entity
			}
		);
	};

	SELF.prototype.getLexemeFormListView = function ( startEditingCallback ) {

		return this._getView(
			'lexemeformlistview',
			$( '.wikibase-lexeme-forms-section' ),
			{
				getListItemAdapter: this.getListItemAdapterForLexemeFormListView.bind( this, startEditingCallback ),
				getAdder: this._getAdderWithStartEditing( startEditingCallback )
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

	SELF.prototype.getLexemeFormView = function ( value, $dom, startEditingCallback, removeCallback ) {
		var lexemeFormView = this._getView(
			'lexemeformview',
			$dom,
			{ value: value }
			),
			controller = this._getController(
				this._toolbarFactory.getToolbarContainer( lexemeFormView.element ),
				lexemeFormView,
				fakeModel,
				removeCallback,
				value,
				startEditingCallback
			);

		// Empty formviews (added with the "add" button) should start in edit mode
		if ( !value ) {
			controller.startEditing()
				.done( $.proxy( lexemeFormView, 'focus' ) );
		}

		return lexemeFormView;
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

				if ( $element.text() !== '' ) { // FIXME: values should come from lexeme object
					value = new wb.lexeme.datamodel.LexemeForm(
						$element.find( '.wikibase-lexeme-form-id' ).text().match( /\d+/ )[ 0 ],
						$element.find( '.wikibase-lexeme-form-text' ).text()
					);
				}

				view = self.getLexemeFormView(
					value || null, // FIXME: if this is undefined instead of null, things break
					$element,
					startEditingCallback,
					doRemove // FIXME: This is not doing the right thing
				);

				return view;
			}
		} );
	};

	wb.lexeme.view.ControllerViewFactory = SELF;

}( mediaWiki, wikibase, jQuery ) );
