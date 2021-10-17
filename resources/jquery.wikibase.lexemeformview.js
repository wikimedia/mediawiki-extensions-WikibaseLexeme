( function ( wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

	var RepresentationWidget = require( './widgets/RepresentationWidget.js' ),
		LexemeStore = require( './store/index.js' ),
		LexemeSubEntityId = require( './datamodel/LexemeSubEntityId.js' ),
		datamodel = require( 'wikibase.datamodel' );

	require( './jquery.wikibase.grammaticalfeatureview.js' );

	/**
	 * Initializes StatementGroupListView on given DOM element
	 *
	 * @callback buildStatementGroupListView
	 * @param {wikibase.lexeme.datamodel.Form}
	 * @param {jQuery} JQuery DOM element
	 */

	/**
	 * @class jQuery.wikibase.lexemeformview
	 * @extends jQuery.ui.EditableTemplatedWidget
	 * @license GPL-2.0-or-later
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {wikibase.lexeme.datamodel.Form} options.value
	 * @param {Function} options.buildStatementGroupListView
	 * @param {wikibase.LabelFormattingService} options.labelFormattingService
	 * @param {mw.Api} options.api
	 * @param {string} options.inputNodeName
	 */
	$.widget( 'wikibase.lexemeformview', PARENT, {
		options: {
			template: 'wikibase-lexeme-form',
			templateParams: [
				function () {
					var $container = $( '<span>' );
					this.deferredFormWithId.promise().then( function ( form ) {
						$container.text( form.getId() );
					} );

					return $container;
				},
				'',
				function () {
					return mw.wbTemplate( 'wikibase-lexeme-form-grammatical-features', '' );
				},
				function () {
					var $container = $( '<div>' );
					this.deferredFormWithId.promise().then( function ( form ) {
						var messageKey = 'wikibaselexeme-statementsection-statements-about-form';
						var $header = $( '<h2>' ).applyTemplate(
							'wb-section-heading',
							[
								// eslint-disable-next-line mediawiki/msg-doc
								mw.message( messageKey, form.getId() ).escaped(),
								'',
								'wikibase-statements'
							]
						);
						$container.append( $header );

						var $statements = $( '<div>' );
						this.options.buildStatementGroupListView(
							form,
							$statements,
							LexemeSubEntityId.getIdSuffix( form.getId() )
						);
						$container.append( $statements );
					}.bind( this ) );

					return $container;
				},
				function () { // Anchor
					this.deferredFormWithId.promise().then( function ( form ) {
						this.element.attr( 'id', LexemeSubEntityId.getIdSuffix( form.getId() ) );
					}.bind( this ) );

					return '';
				}
			],
			templateShortCuts: {
				$id: '.wikibase-lexeme-form-id',
				$grammaticalFeatures: '.wikibase-lexeme-form-grammatical-features',
				$representations: '.form-representations'
			},
			inputNodeName: 'TEXTAREA',
			api: null,

			/**
			 * @type {buildStatementGroupListView}
			 */
			buildStatementGroupListView: null
		},
		_inEditMode: false,

		_grammaticalFeatureView: null,

		_representationsWidget: null,

		/**
		 * This method acts as a setter if it is given a Form object.
		 * Otherwise it returns its value if it is not in edit mode and returns a new Form from its
		 * input value otherwise.
		 *
		 * @param {wikibase.lexeme.datamodel.Form} form
		 * @return {wikibase.lexeme.datamodel.Form|undefined|null}
		 */
		value: function ( form ) {
			if ( form instanceof wb.lexeme.datamodel.Form ) {
				this.option( 'value', form );
				this._grammaticalFeatureView.value( form.getGrammaticalFeatures() );
				this._representationsWidget.replaceAllRepresentations( termMapToArray( form.getRepresentations() ) );
				if ( this.deferredFormWithId && form.getId() ) {
					this.deferredFormWithId.resolve( form );
					this.deferredFormWithId = null;
				}
				this.draw();
				return;
			}

			if ( !this.isInEditMode() ) {
				return this.options.value;
			}

			if ( this._representationsWidget.hasRedundantLanguage ) {
				return null;
			}

			return new wb.lexeme.datamodel.Form(
				this.options.value ? this.options.value.getId() : null,
				arrayToTermMap( this._representationsWidget.representations ),
				this._grammaticalFeatureView ? this._grammaticalFeatureView.value() : []
			);
		},

		_create: function () {
			this.deferredFormWithId = $.Deferred();

			PARENT.prototype._create.call( this );

			this._grammaticalFeatureView = this._buildGrammaticalFeatureView();
			this.options.buildStatementGroupListView(
				this.value(),
				$( '.wikibase-statementgrouplistview', this.element ),
				this.value().getId() === undefined ? '' : LexemeSubEntityId.getIdSuffix( this.value().getId() )
			);

			this._buildRepresentations( this.value() );
		},

		_buildGrammaticalFeatureView: function buildGrammaticalFeatureView() {
			var self = this;

			var value = this.value() ? this.value().getGrammaticalFeatures() : [];
			var labelFormattingService = this.options.labelFormattingService;
			this.$grammaticalFeatures.grammaticalfeatureview( {
				value: value,
				labelFormattingService: labelFormattingService,
				api: self.options.api
			} );

			this.$grammaticalFeatures.on( 'grammaticalfeatureviewchange', function () {
				self._trigger( 'change' );
			} );

			return this.$grammaticalFeatures.data( 'grammaticalfeatureview' );
		},

		_startEditing: function () {
			this._inEditMode = true;
			this._grammaticalFeatureView.startEditing();
			this._representationsWidget.edit();
			return this.draw();
		},

		_stopEditing: function ( dropValue ) {
			this._inEditMode = false;
			if ( dropValue ) {
				this._representationsWidget.representations = termMapToArray( this.value().getRepresentations() );
			}
			this._grammaticalFeatureView.stopEditing( dropValue );
			this._representationsWidget.stopEditing();

			return this.draw();
		},

		isInEditMode: function () {
			return this._inEditMode;
		},

		_buildRepresentations: function ( form ) {
			var representations = termMapToArray( form.getRepresentations() ),
				lemmas = termMapToArray( this.options.lexeme.getLemmas() );

			var template = mw.template.get( 'wikibase.lexeme.lexemeview', 'representations.vue' ).getSource();

			this._representationsWidget = RepresentationWidget.create(
				getStore(
					lemmas,
					getFormIndex(),
					form.getId(),
					representations
				),
				getFormIndex(),
				this.$representations[ 0 ],
				template,
				function () {
					this._trigger( 'change' );
				}.bind( this ),
				mw
			);
		},

		/**
		 * @inheritdoc
		 */
		draw: function () {
			var deferred = $.Deferred(),
				value = this.options.value;
			if ( !value || value.getRepresentations().isEmpty() ) {
				value = null;
			}

			if ( !this.isInEditMode() && !value ) {
				// Apply lang and dir of UI language
				// instead language of that row
				var userLanguage = mw.config.get( 'wgUserLanguage' );
				this.element
					.attr( 'lang', userLanguage )
					.attr( 'dir', $.util.getDirectionality( userLanguage ) );
				return deferred.resolve().promise();
			}

			return deferred.resolve().promise();
		}
	} );

	/**
	 * @param {{language:string, value: string}[]} termArray
	 * @return {datamodel.TermMap}
	 */
	function arrayToTermMap( termArray ) {
		var result = new datamodel.TermMap();

		termArray.forEach( function ( representation ) {
			try {
				result.setItem(
					representation.language,
					new datamodel.Term( representation.language, representation.value )
				);
			} catch ( e ) {
				// ignore
			}
		} );

		return result;
	}

	/**
	 * @param {datamodel.TermMap} termMap
	 * @return {Array}
	 */
	function termMapToArray( termMap ) {
		var result = [];

		termMap.each( function ( language, term ) {
			result.push( { language: term.getLanguageCode(), value: term.getText() } );
		} );

		return result;
	}

	// TODO If multiple lexemeformview shared one store:
	// Recompute when forms are removed/added, so child components do not emit wrong formIndex!
	function getFormIndex() {
		return 0;
	}

	/**
	 * Creates a separate store per form - with all resulting limitations
	 * TODO Continue refactoring by moving store creation upstream (ControllerViewFactory)
	 *
	 * @param lemmas
	 * @param formIndex
	 * @param formId
	 * @param representations
	 * @return {Vuex.Store}
	 */
	function getStore( lemmas, formIndex, formId, representations ) {
		var forms = {};
		forms[ formIndex ] = {
			id: formId,
			representations: representations
		};

		return LexemeStore.create( lemmas, forms );
	}

}( wikibase ) );
