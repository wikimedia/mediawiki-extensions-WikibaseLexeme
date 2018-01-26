( function ( $, mw, wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

	/** @type {wikibase.lexeme.widgets.RepresentationWidget} */
	var RepresentationWidget = require( 'wikibase.lexeme.widgets.RepresentationWidget' );

	/**
	 * Initializes StatementGroupListView on given DOM element
	 * @callback buildStatementGroupListView
	 * @param {wikibase.lexeme.datamodel.Form}
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
	 * @param {wikibase.lexeme.datamodel.Form} options.value
	 * @param {Function} options.buildStatementGroupListView
	 * @param {wikibase.LabelFormattingService} options.labelFormattingService
	 * @param {mediaWiki.Api} options.api
	 * @param {string} options.inputNodeName
	 */
	$.widget( 'wikibase.lexemeformview', PARENT, {
		options: {
			template: 'wikibase-lexeme-form',
			templateParams: [
				function () {
					var $container = $( '<span/>' );
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
					var $container = $( '<div/>' );
					this.deferredFormWithId.promise().then( function ( form ) {
						var messageKey = 'wikibaselexeme-statementsection-statements-about-form';
						var $header = $( '<h2/>' ).applyTemplate(
							'wb-section-heading',
							[
								mw.message( messageKey, form.getId() ).escaped(),
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
				},
				function () { //Anchor
					this.deferredFormWithId.promise().then( function ( form ) {
						this.element.attr( 'id', form.getId() );
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
		 * @return {wikibase.lexeme.datamodel.Form|undefined}
		 */
		value: function ( form ) {
			if ( form instanceof wb.lexeme.datamodel.Form ) {
				this.option( 'value', form );
				this._grammaticalFeatureView.value( form.getGrammaticalFeatures() );
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
				$( '.wikibase-statementgrouplistview', this.element )
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
			var representations = form ? termMapToArray( form.getRepresentations() ) : [];

			this._representationsWidget = RepresentationWidget.create(
				representations,
				this.$representations[ 0 ],
				'#representation-widget-vue-template',
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

	function arrayToTermMap( representations ) {
		var result = new wb.datamodel.TermMap();

		representations.forEach( function ( representation ) {
			try {
				result.setItem(
					representation.language,
					new wb.datamodel.Term( representation.language, representation.value )
				);
			} catch ( e ) {
				// ignore
			}
		} );

		return result;
	}

	/**
	 * @param {wikibase.datamodel.TermMap} representations
	 * @return {Array}
	 */
	function termMapToArray( representations ) {
		var result = [];

		representations.each( function ( language, term ) {
			result.push( { language: term.getLanguageCode(), value: term.getText() } );
		} );

		return result;
	}
}( jQuery, mediaWiki, wikibase ) );
