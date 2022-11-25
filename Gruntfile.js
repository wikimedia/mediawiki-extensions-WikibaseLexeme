/* eslint-env node */

module.exports = function ( grunt ) {
	var conf = grunt.file.readJSON( 'extension.json' );

	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-jasmine-nodejs' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	require( 'module-alias/register' );

	grunt.initConfig( {
		eslint: {
			options: {
				cache: true,
				fix: grunt.option( 'fix' )
			},
			all: [
				'**/*.{js,json}',
				'!node_modules/**',
				'!resources/special/new-lexeme/**',
				'!resources/special/new-lexeme-dist/**',
				'!vendor/**'
			]
		},
		stylelint: {
			options: {
				fix: grunt.option( 'fix' )
			},
			all: [
				'**/*.less',
				'!node_modules/**',
				'!resources/special/new-lexeme/**',
				'!resources/special/new-lexeme-dist/**',
				'!vendor/**'
			]
		},
		// eslint-disable-next-line es/no-object-assign, compat/compat
		banana: Object.assign(
			conf.MessagesDirs,
			{
				options: {
					requireLowerCase: 'initial'
				}
			}
		),
		// eslint-disable-next-line camelcase
		jasmine_nodejs: {
			all: {
				options: {
					random: true
				},
				specs: [
					'tests/jasmine/**/*.spec.js'
				],
				helpers: [
					'tests/jasmine/helpers/*.js'
				]
			}
		}
	} );

	grunt.registerTask( 'test', [ 'eslint:all', 'banana', 'jasmine_nodejs', 'stylelint' ] );
	grunt.registerTask( 'fix', function () {
		grunt.config.set( 'eslint.options.fix', true );
		grunt.task.run( 'eslint' );
		grunt.config.set( 'stylelint.options.fix', true );
		grunt.task.run( 'stylelint' );
	} );
	grunt.registerTask( 'default', 'test' );
};
