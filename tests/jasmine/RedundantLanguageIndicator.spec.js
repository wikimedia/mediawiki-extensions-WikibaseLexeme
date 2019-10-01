describe( 'RedundantLanguageIndicator', function () {
	var expect = require( 'unexpected' ).clone(),
		sinon = require( 'sinon' );

	var RedundantLanguageIndicator = require( './../../resources/widgets/RedundantLanguageIndicator.js' );

	it( 'creates mixin definition that adds a redundantLanguages property to data', function () {
		var mixin = RedundantLanguageIndicator( 'myprop' );

		expect( mixin.data, 'to be a function' );
		expect( mixin.data(), 'to equal', { redundantLanguages: [] } );
	} );

	it( 'creates mixin definition with watch on desired property', function () {
		var mixin = RedundantLanguageIndicator( 'myprop' );

		expect( mixin.watch, 'to have property', 'myprop' );
	} );

	it( 'creates mixin definition with watch that fires immediately', function () {
		var mixin = RedundantLanguageIndicator( 'myprop' );

		expect( mixin.watch.myprop.immediate, 'to be truthy' );
	} );

	it( 'creates mixin definition with watch that monitors the property recursively', function () {
		var mixin = RedundantLanguageIndicator( 'myprop' );

		expect( mixin.watch.myprop.deep, 'to be truthy' );
	} );

	it( 'creates mixin definition providing method to determine if language isRedundantLanguage', function () {
		var mixin = RedundantLanguageIndicator( 'myprop' );

		expect( mixin.methods.isRedundantLanguage, 'to be a function' );
	} );

	it( 'creates mixin definition method isRedundantLanguage returning false for empty redundantLanguages', function () {
		var mixin = RedundantLanguageIndicator( 'myprop' ),
			isRedundantLanguage = mixin.methods.isRedundantLanguage;

		expect( isRedundantLanguage.call( { redundantLanguages: [] } ), 'to be falsy' );
	} );

	it( 'creates mixin definition providing computed property hasRedundantLanguage', function () {
		var mixin = RedundantLanguageIndicator( 'myprop' );

		expect( mixin.computed.hasRedundantLanguage, 'to be a function' );
	} );

	it( 'creates mixin property hasRedundantLanguage returning false for empty redundantLanguages', function () {
		var mixin = RedundantLanguageIndicator( 'myprop' ),
			hasRedundantLanguage = mixin.computed.hasRedundantLanguage;

		expect( hasRedundantLanguage.call( { redundantLanguages: [] } ), 'to be falsy' );
	} );

	it( 'creates mixin property hasRedundantLanguage returning true for existing redundantLanguages', function () {
		var mixin = RedundantLanguageIndicator( 'myprop' ),
			hasRedundantLanguage = mixin.computed.hasRedundantLanguage;

		expect( hasRedundantLanguage.call( { redundantLanguages: [ 'en' ] } ), 'to be truthy' );
	} );

	it( 'creates mixin watch handler that updates redundantLanguages with respective language values', function () {
		var mixin = RedundantLanguageIndicator( 'myprop' ),
			detectRedundantLanguages = mixin.watch.myprop.handler,
			instance = {
				get hasRedundantLanguage() {
					return mixin.computed.hasRedundantLanguage.call( instance );
				},
				$emit: sinon.spy()
			};

		detectRedundantLanguages.call(
			instance,
			[
				{ language: 'de', value: 'something' },
				{ language: 'fr', value: 'autre' },
				{ language: 'de', value: 'something else' }
			]
		);
		expect( instance.redundantLanguages, 'to equal', [ 'de' ] );
		expect( instance.$emit.withArgs( 'hasRedundantLanguage', true ).calledOnce, 'to be truthy' );
	} );

	it( 'creates mixin watch handler that can find multiple redundant languages', function () {
		var mixin = RedundantLanguageIndicator( 'myprop' ),
			detectRedundantLanguages = mixin.watch.myprop.handler,
			instance = {
				get hasRedundantLanguage() {
					return mixin.computed.hasRedundantLanguage.call( instance );
				},
				$emit: sinon.spy()
			};

		detectRedundantLanguages.call(
			instance,
			[
				{ language: 'en', value: 'color' },
				{ language: 'de', value: 'Farbe' },
				{ language: 'en', value: 'colour' },
				{ language: 'de', value: 'Kolorierung' }
			]
		);
		expect( instance.redundantLanguages, 'to equal', [ 'en', 'de' ] );
		expect( instance.$emit.withArgs( 'hasRedundantLanguage', true ).calledOnce, 'to be truthy' );
	} );

	it( 'creates mixin watch handler not taking offence in repeated empty language', function () {
		var mixin = RedundantLanguageIndicator( 'myprop' ),
			detectRedundantLanguages = mixin.watch.myprop.handler,
			instance = {
				get hasRedundantLanguage() {
					return mixin.computed.hasRedundantLanguage.call( instance );
				},
				$emit: sinon.spy()
			};

		detectRedundantLanguages.call(
			instance,
			[
				{ language: 'en', value: 'example' },
				{ language: '', value: '' },
				{ language: '', value: '' }
			]
		);
		expect( instance.redundantLanguages, 'to equal', [] );
		expect( instance.$emit.withArgs( 'hasRedundantLanguage', false ).calledOnce, 'to be truthy' );
	} );

} );
