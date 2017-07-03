/**
 * @license GPL-2.0+
 */
( function ( $, wb, QUnit ) {
	'use strict';

	/** @type {wikibase.datamodel.TermMap}*/
	var TermMap = wb.datamodel.TermMap;
	/** @type {wikibase.datamodel.Term}*/
	var Term = wb.datamodel.Term;

	var selector = {
		representationTextInput: '.representation-widget_representation-value-input',
		representationLanguageInput: '.representation-widget_representation-language-input',
		representationText: '.representation-widget_representation-value'
	};

	QUnit.module( 'jquery.wikibase.lexemeformview', QUnit.newMwEnvironment( {
		setup: function () {
			$( '<script id="representation-widget-vue-template" type="x-template"/>' )
				.html( getRepresentationWidgetTemplate() )
				.appendTo( '#qunit-fixture' );
		}
	} ) );

	var newFormView = function ( options ) {
		var $node = $( '<div/>' ).appendTo( '#qunit-fixture' );

		options = options || {};

		options.api = options.api || {};
		options.language = options.language || 'en';
		options.labelFormattingService = options.labelFormattingService || {
			getHtml: function ( id ) {
				return $.Deferred().resolve( id ).promise();
			}
		};
		options.buildStatementGroupListView = function () {};

		return $node.lexemeformview( options ).data( 'lexemeformview' );
	};

	var newForm = function ( id, defaultRepresentation ) {
		var representations = new TermMap( { en: new Term( 'en', defaultRepresentation ) } );

		return new wb.lexeme.datamodel.Form( id, representations );
	};

	QUnit.test( 'can be created', function ( assert ) {
		assert.ok( newFormView() instanceof $.wikibase.lexemeformview );
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
		var done = assert.async();
		var view = newFormView(),
			textInput = 'foobar';

		view.startEditing().then( function () {
			changeInputValue( view.element.find( selector.representationLanguageInput ), 'en' );
			changeInputValue( view.element.find( selector.representationTextInput ), textInput );

			assert.equal(
				view.value().getRepresentations().getItemByKey( 'en' ).getText(),
				textInput
			);
		} ).catch( function ( e ) {
			assert.notOk( e.stack );
		} ).then( done );
	} );

	QUnit.test( 'should not be in edit mode when initialized without a value', function ( assert ) {
		assert.notOk( newFormView().isInEditMode() );
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

	/**
	 * Sets input value and triggers 'input'
	 * @param {jQuery}$element
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
			'<span class="representation-widget_representation-value">{{representation.value}}</span>\n' +
			'<span class="representation-widget_representation-language">\n' +
			'{{representation.language}}\n' +
			'</span>\n' +
			'</li>\n' +
			'</ul>\n' +
			'<div v-else>\n' +
			'<div class="representation-widget_edit-area">\n' +
			'<ul class="representation-widget_representation-list">\n' +
			'<li v-for="representation in representations" \n' +
			'class="representation-widget_representation-edit-box">\n' +
			'<input size="1" class="representation-widget_representation-value-input" \n' +
			'v-model="representation.value">\n' +
			'<input size="1" class="representation-widget_representation-language-input" \n' +
			'v-model="representation.language">\n' +
			'<button class="representation-widget_representation-remove" \n' +
			'v-on:click="remove(representation)" \n' +
			':title="\'wikibase-remove\'|message">\n' +
			'&times;\n' +
			'</button>\n' +
			'</li>\n' +
			'<li>\n' +
			'<button type="button" class="representation-widget_add" v-on:click="add" \n' +
			':title="\'wikibase-add\'|message">+</button>\n' +
			'</li>\n' +
			'</ul>\n' +
			'</div>\n' +
			'</div>\n' +
			'</div>';
	}

}( jQuery, wikibase, QUnit ) );
