( function ( $, mw ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

	/**
	 * Initializes StatementGroupListView on given DOM element
	 * @callback buildStatementGroupListView
	 * @param {wikibase.datamodel.LexemeForm}
	 * @param {jQuery} JQuery DOM element
	 */

	/**
	 * @class jQuery.wikibase.lexemeformview
	 * @extends jQuery.ui.EditableTemplatedWidget
	 * @license GPL-2.0+
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {wikibase.datamodel.Forms} options.value
	 */
	$.widget( 'wikibase.lexemeformview', PARENT, {
		options: {
			template: 'wikibase-lexeme-form',
			templateParams: [
				function () {
					return 'some lang';
				},
				'',
				function () {
					return '';
				},
				function () {
					return mw.wbTemplate( 'wikibase-lexeme-form-grammatical-features', '' );
				},
				function () {
					var $container = $( '<div/>' );
					this.deferredFormWithId = $.Deferred();

					this.deferredFormWithId.promise().then( function ( form ) {
						var $header = $( '<h2/>' ).applyTemplate(
							'wb-section-heading',
							[
								mw.message( 'wikibase-statementsection-statements' ).escaped(),
								'',
								'wikibase-statements'
							]
						);
						$container.append( $header );

						var $statements = $( '<div/>' );
						this.options.buildStatementGroupListView(
							form,
							$statements
						);
						$container.append( $statements );
					}.bind( this ) );

					return $container;
				}
			],
			templateShortCuts: {
				$text: '.wikibase-lexeme-form-text',
				$id: '.wikibase-lexeme-form-id',
				$grammaticalFeatures: '.wikibase-lexeme-form-grammatical-features'
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

		/**
		 * This method acts as a setter if it is given a LexemeForm object.
		 * Otherwise it returns its value if it is not in edit mode and returns a new LexemeForm from its
		 * input value otherwise.
		 *
		 * @param {wikibase.lexeme.datamodel.LexemeForm} form
		 * @return {wikibase.lexeme.datamodel.LexemeForm|undefined}
		 */
		value: function ( form ) {
			if ( form instanceof wikibase.lexeme.datamodel.LexemeForm ) {
				this.option( 'value', form );
				this._grammaticalFeatureView.value( form.getGrammaticalFeatures() );
				if ( this.deferredFormWithId ) {
					if ( form.getId() ) {
						this.deferredFormWithId.resolve( form );
						this.deferredFormWithId = null;
					}
				}
				this.draw();
				return;
			}

			if ( !this.isInEditMode() ) {
				return this.options.value;
			}

			return new wikibase.lexeme.datamodel.LexemeForm(
				null,
				$.trim( this.$text.children( this.inputNodeName ).val() ),
				this._grammaticalFeatureView ? this._grammaticalFeatureView.value() : []
			);
		},

		_create: function () {
			PARENT.prototype._create.call( this );

			this._grammaticalFeatureView = this._buildGrammaticalFeatureView();
			this.options.buildStatementGroupListView(
				this.value(),
				$( '.wikibase-statementgrouplistview', this.element )
			);
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

			return this.$grammaticalFeatures.data( 'grammaticalfeatureview' );
		},

		_startEditing: function () {
			this._inEditMode = true;
			this._grammaticalFeatureView.startEditing();// FIXME this line breaks edit mode when adding lexeme form
			return this.draw();
		},

		_stopEditing: function ( dropValue ) {
			this._inEditMode = false;
			if ( dropValue && this.options.value.getRepresentation() === '' ) {
				this.$text.children( this.inputNodeName ).val( '' );
			}
			this._grammaticalFeatureView.stopEditing( dropValue );

			return this.draw();
		},

		isInEditMode: function () {
			return this._inEditMode;
		},

		/**
		 * @inheritdoc
		 */
		draw: function () {
			var deferred = $.Deferred(),
				value = this.options.value;
			if ( !value || value.getRepresentation() === '' ) {
				value = null;
			}

			if ( !this.isInEditMode() && !value ) {
				this.$text.text( mw.msg( 'wikibase-lexeme-empty-form-representation' ) );
				// Apply lang and dir of UI language
				// instead language of that row
				var userLanguage = mw.config.get( 'wgUserLanguage' );
				this.element
					.attr( 'lang', userLanguage )
					.attr( 'dir', $.util.getDirectionality( userLanguage ) );
				return deferred.resolve().promise();
			}

			if ( !this.isInEditMode() ) {
				this.$text.text( value.getRepresentation() );
				this.$id.text( ' (F' + value.getId() + ')' ); // TODO: whitespace and brackets (?) should be i18nable

				return deferred.resolve().promise();
			}

			var $input = $( document.createElement( this.options.inputNodeName ) )
				.attr( 'placeholder', mw.msg( 'wikibase-lexeme-enter-form-representation' ) );

			if ( value ) {
				$input.val( value.getRepresentation() );
			}

			if ( $.fn.inputautoexpand ) {
				$input.inputautoexpand( {
					suppressNewLine: true
				} );
			}

			this.$text.empty().append( $input );

			return deferred.resolve().promise();
		}
	} );
}( jQuery, mediaWiki ) );
