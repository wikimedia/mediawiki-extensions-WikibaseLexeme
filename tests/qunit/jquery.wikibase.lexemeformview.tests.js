/**
 * @license GPL-2.0-or-later
 */
( function ( require, wb ) {
	'use strict';

	var Vue = require( 'vue' );
	var datamodel = require( 'wikibase.datamodel' );

	/** @type {wikibase.datamodel.TermMap} */
	var TermMap = datamodel.TermMap;
	/** @type {wikibase.datamodel.Term} */
	var Term = datamodel.Term;

	var selector = {
		representationTextInput: '.representation-widget_representation-value-input',
		representationLanguageInput: '.representation-widget_representation-language-input',
		representationText: '.representation-widget_representation-value',
		languageRedundantWarning: '.representation-widget_redundant-language-warning',
		representationLanguageRedundant: '.representation-widget_representation-language-input_redundant-language'
	};

	QUnit.module( 'jquery.wikibase.lexemeformview', QUnit.newMwEnvironment() );

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
				view.element.find( selector.representationText ).text().trim(),
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
	 *
	 * @param {jQuery} $element
	 * @param {string} newValue
	 */
	function changeInputValue( $element, newValue ) {
		$element.val( newValue );
		var event = document.createEvent( 'Event' );
		event.initEvent( 'input', true, true );
		$element[ 0 ].dispatchEvent( event );
	}

}( require, wikibase ) );
