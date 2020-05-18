/**
 * @license GPL-2.0-or-later
 */
( function ( require, wb ) {
	'use strict';

	var Vue = require( 'vue2' );
	var datamodel = require( 'wikibase.datamodel' );

	/** @type {wikibase.datamodel.TermMap}*/
	var TermMap = datamodel.TermMap;
	/** @type {wikibase.datamodel.Term}*/
	var Term = datamodel.Term;

	var selector = {
		representationTextInput: '.representation-widget_representation-value-input',
		representationLanguageInput: '.representation-widget_representation-language-input',
		representationText: '.representation-widget_representation-value',
		languageRedundantWarning: '.representation-widget_redundant-language-warning',
		representationLanguageRedundant: '.representation-widget_representation-language-input_redundant-language'
	};

	QUnit.module( 'jquery.wikibase.lexemeformview', QUnit.newMwEnvironment( {
		setup: function () {
			$( '<script id="representation-widget-vue-template" type="x-template"/>' )
				.html( getRepresentationWidgetTemplate() )
				.appendTo( '#qunit-fixture' );
		}
	} ) );

	var newFormView = function ( options ) {
		var $node = $( '<div>' ).appendTo( '#qunit-fixture' );

		options = options || {};

		options.api = options.api || {};
		options.language = options.language || 'en';
		options.labelFormattingService = options.labelFormattingService || {
			getHtml: function ( id ) {
				return $.Deferred().resolve( id ).promise();
			}
		};
		options.buildStatementGroupListView = options.buildStatementGroupListView || function () {};
		options.lexeme = {
			getLemmas: function () {
				return new TermMap( { en: new Term( 'en', 'color' ) } );
			}
		};

		return $node.lexemeformview( options ).data( 'lexemeformview' );
	};

	var newForm = function ( id, defaultRepresentation ) {
		var representations = new TermMap( { en: new Term( 'en', defaultRepresentation ) } );

		return new wb.lexeme.datamodel.Form( id, representations );
	};

	QUnit.test( 'can be created', function ( assert ) {
		var form = newForm( 'F123', 'foo' );

		assert.ok( newFormView( { value: form } ) instanceof $.wikibase.lexemeformview );
	} );

	QUnit.test( 'creation without injected option.value fails', function ( assert ) {
		try {
			newFormView();
			assert.notOk( true, 'Expecting construction to fail without form value to work with' );
		} catch ( e ) {
			assert.ok( e );
		}
	} );

	QUnit.test( 'value can be injected as option.value', function ( assert ) {
		var form = newForm( 'F123', 'foo' ),
			view = newFormView( {
				value: form
			} );

		assert.equal( view.value(), form );
	} );

	QUnit.test( 'value() sets internal value', function ( assert ) {
		var form1 = newForm( 'F123', 'foo' ),
			form2 = newForm( 'F234', 'bar' ),
			view = newFormView( {
				value: form1
			} );

		view.value( form2 );
		assert.equal( view.value(), form2 );
	} );

	QUnit.test( 'value() creates value from input if it is in edit mode', function ( assert ) {
		var done = assert.async(),
			form1 = newForm( 'F123', 'foo' ), // creates 'en' representation 'foo'
			view = newFormView( { value: form1 } ),
			textInput = 'foobar';

		view.startEditing().then( function () {
			changeInputValue( view.element.find( selector.representationLanguageInput ), 'en-gb' );
			changeInputValue( view.element.find( selector.representationTextInput ), textInput );

			assert.equal(
				view.value().getRepresentations().getItemByKey( 'en-gb' ).getText(),
				textInput
			);
		} ).catch( function ( e ) {
			assert.notOk( e.stack );
		} ).then( done );
	} );

	QUnit.test( 'value() creates null value when in edit mode with redundant languages', function ( assert ) {
		var done = assert.async(),
			form1 = newForm( 'F123', 'foo' ), // creates 'en' representation 'foo'
			view = newFormView( {
				value: form1
			} );

		view.startEditing().then( function () {
			view._representationsWidget.add();

			Vue.nextTick( function () {
				changeInputValue( view.element.find( selector.representationLanguageInput ).last(), 'en' );
				changeInputValue( view.element.find( selector.representationTextInput ).last(), 'conflicting' );

				Vue.nextTick( function () {
					assert.equal(
						view.value(),
						null
					);
				} );
			} );

		} ).catch( function ( e ) {
			assert.notOk( e.stack );
		} ).then( done );
	} );

	QUnit.test( 'shows warning when in edit mode with redundant languages', function ( assert ) {
		var done = assert.async(),
			form1 = newForm( 'F123', 'foo' ), // creates 'en' representation 'foo'
			view = newFormView( {
				value: form1
			} );

		view.startEditing().then( function () {
			view._representationsWidget.add();

			Vue.nextTick( function () {
				changeInputValue( view.element.find( selector.representationLanguageInput ).last(), 'en' );
				changeInputValue( view.element.find( selector.representationTextInput ).last(), 'conflicting' );

				Vue.nextTick( function () {
					assert.equal(
						view.element.find( selector.languageRedundantWarning ).length,
						1
					);
				} );
			} );

		} ).catch( function ( e ) {
			assert.notOk( e.stack );
		} ).then( done );
	} );

	QUnit.test( 'marks redundant languages when in edit mode with redundant languages', function ( assert ) {

		var done = assert.async(),
			form1 = newForm( 'F123', 'foo' ), // creates 'en' representation 'foo'
			view = newFormView( {
				value: form1
			} );

		view.startEditing().then( function () {
			view._representationsWidget.add();

			Vue.nextTick( function () {
				changeInputValue( view.element.find( selector.representationLanguageInput ).last(), 'en' );
				changeInputValue( view.element.find( selector.representationTextInput ).last(), 'conflicting' );

				Vue.nextTick( function () {
					assert.equal(
						view.element.find( selector.representationLanguageRedundant ).length,
						2
					);
				} );
			} );

		} ).catch( function ( e ) {
			assert.notOk( e.stack );
		} ).then( done );
	} );

	QUnit.test( 'should not be in edit mode by default when initialized with a value', function ( assert ) {
		assert.notOk( newFormView( { value: newForm( 'F123', 'foo' ) } ).isInEditMode() );
	} );

	QUnit.test( 'draws value in input node after startEditing()', function ( assert ) {
		var done = assert.async();
		var form = newForm( 'F123', 'foobar' ),
			view = newFormView( {
				value: form
			} );

		view.startEditing().then( function () {
			assert.equal(
				view.element.find( selector.representationTextInput ).val(),
				form.getRepresentations().getItemByKey( 'en' ).getText()
			);
		} ).catch( function ( e ) {
			assert.notOk( e.stack );
		} ).then( done );

	} );

	QUnit.test( 'draws value in text node after stopEditing()', function ( assert ) {
		var done = assert.async();

		var form = newForm( 'F123', 'foobar' ),
			view = newFormView( {
				value: form
			} );

		view.startEditing().then( function () {
			return view.stopEditing();
		} ).then( function () {
			assert.equal(
				view.element.find( selector.representationText ).text(),
				form.getRepresentations().getItemByKey( 'en' ).getText()
			);
		} ).catch( function ( e ) {
			assert.notOk( e.stack );
		} ).then( done );
	} );

	QUnit.test( 'Given a value, creates StatementGroupListView with Form id prefix', function ( assert ) {
		var formId = 'L1-F123',
			form = newForm( formId, 'potatoes' ),
			statementGroupListViewSpy = sinon.spy();

		newFormView( {
			value: form,
			buildStatementGroupListView: statementGroupListViewSpy
		} );

		assert.ok( statementGroupListViewSpy.calledWith(
			form,
			sinon.match.any,
			'F123'
		) );
	} );

	QUnit.test( 'Given a new form, creates StatementGroupListView with empty prefix', function ( assert ) {
		var form = new wb.lexeme.datamodel.Form(), // i.e. default 'undefined' id
			statementGroupListViewSpy = sinon.spy();

		newFormView( {
			value: form,
			buildStatementGroupListView: statementGroupListViewSpy
		} );

		assert.ok( statementGroupListViewSpy.calledWith(
			form,
			sinon.match.any,
			''
		) );
	} );

	QUnit.test( 'sets id after form save', function ( assert ) {
		var emptyForm = new wikibase.lexeme.datamodel.Form(
				'',
				new TermMap()
			),
			statementGroupListViewSpy = sinon.spy(),
			view = newFormView( {
				value: emptyForm,
				buildStatementGroupListView: statementGroupListViewSpy
			} ),
			done = assert.async();

		view.deferredFormWithId.resolve( newForm( 'L321-F123', 'meow' ) );

		view.deferredFormWithId.promise().then( function () {
			assert.equal(
				view.element.attr( 'id' ),
				'F123'
			);
			assert.ok( statementGroupListViewSpy.calledWith(
				sinon.match.any,
				sinon.match.any,
				'F123'
			) );
			done();
		} );
	} );

	QUnit.test( 'form inputs should have connected labels', function ( assert ) {
		var done = assert.async(),
			form1 = newForm( 'F123', 'foo' ),
			view = newFormView( { value: form1 } );

		view.startEditing().then( function () {
			var languageId = view.element.find( selector.representationLanguageInput )[ 0 ].id;
			var languageLabelsFound = ( view.element.find( 'label[for=' + languageId + ']' ) );

			var textId = view.element.find( selector.representationTextInput )[ 0 ].id;
			var textLabelsFound = ( view.element.find( 'label[for=' + textId + ']' ) );

			assert.ok( languageLabelsFound.length, 1 );
			assert.ok( textLabelsFound.length, 1 );
		} ).catch( function ( e ) {
			assert.notOk( e.stack );
		} ).then( done );
	} );

	/**
	 * Sets input value and triggers 'input'
	 * @param {jQuery} $element
	 * @param {string} newValue
	 */
	function changeInputValue( $element, newValue ) {
		$element.val( newValue );
		var event = document.createEvent( 'Event' );
		event.initEvent( 'input', true, true );
		$element[ 0 ].dispatchEvent( event );
	}

	function getRepresentationWidgetTemplate() {
		return '<div class="representation-widget">\n' +
			'<ul v-if="!inEditMode" class="representation-widget_representation-list">\n' +
			'<li v-for="representation in representations" class="representation-widget_representation">\n' +
			'<span class="representation-widget_representation-value"\n' +
			':lang="representation.language">{{representation.value}}</span>\n' +
			'<span class="representation-widget_representation-language">\n' +
			'{{representation.language}}\n' +
			'</span>\n' +
			'</li>\n' +
			'</ul>\n' +
			'<div v-else>\n' +
			'<div class="representation-widget_edit-area">\n' +
			'<ul class="representation-widget_representation-list">\n' +
			'<li v-for="(representation, index) in representations"\n' +
			'class="representation-widget_representation-edit-box">\n' +
			'<label :for="inputRepresentationId(index)"\n' +
			'class="representation-widget_representation-value-label">\n' +
			'{{\'wikibaselexeme-form-field-representation-label\'|message}}\n' +
			'</label>\n' +
			'<input size="1" class="representation-widget_representation-value-input"\n' +
			':value="representation.value"\n' +
			':id="inputRepresentationId(index)"\n' +
			'@input="updateValue(representation, $event)">\n' +
			'<label :for="inputLanguageId(index)"\n' +
			'class="representation-widget_representation-language-label">\n' +
			'{{\'wikibaselexeme-form-field-language-label\'|message}}\n' +
			'</label>\n' +
			'<input size="1" class="representation-widget_representation-language-input"\n' +
			':id="inputLanguageId(index)"\n' +
			':value="representation.language"\n' +
			'@input="updateLanguage(representation, $event)"\n' +
			':class="{\n' +
			'\'representation-widget_representation-language-input_redundant-language\':\n' +
			'isRedundantLanguage(representation.language)\n' +
			'}"\n' +
			':aria-invalid="isRedundantLanguage(representation.language)">\n' +
			'<button class="representation-widget_representation-remove"\n' +
			'v-on:click="remove(representation)"\n' +
			':disabled="representations.length <= 1"\n' +
			':title="\'wikibase-remove\'|message">\n' +
			'&times;\n' +
			'</button>\n' +
			'</li>\n' +
			'<li class="representation-widget_edit-area-controls">\n' +
			'<button type="button" class="representation-widget_add" v-on:click="add"\n' +
			':title="\'wikibase-add\'|message">+</button>\n' +
			'</li>\n' +
			'</ul>\n' +
			'</div>\n' +
			'<div v-if="hasRedundantLanguage" class="representation-widget_redundant-language-warning">\n' +
			'<p>{{\'wikibaselexeme-form-representation-redundant-language\'|message}}</p>\n' +
			'</div>\n' +
			'</div>\n' +
			'</div>';
	}

}( require, wikibase ) );
