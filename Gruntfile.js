/* eslint-env node */

module.exports = function ( grunt ) {
	var conf = grunt.file.readJSON( 'extension.json' );

	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-jsonlint' );
	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-jasmine-nodejs' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	require( 'module-alias/register' );

	grunt.initConfig( {
		eslint: {
			options: {
				cache: true,
				reportUnusedDisableDirectives: true
			},
			all: [
				'**/*.js',
				'!Gruntfile.js',
				'!node_modules/**',
				'!resources/vendor/**',
				'!vendor/**'
			],
			fix: {
				options: {
					fix: true
				},
				src: [
					'**/*.js',
					'!Gruntfile.js',
					'!node_modules/**',
					'!resources/vendor/**',
					'!vendor/**'
				]
			}
		},
		stylelint: {
			all: [
				'**/*.less',
				'!node_modules/**',
				'!vendor/**'
			]
		},
		banana: Object.assign(
			conf.MessagesDirs,
			{
				options: {
					requireLowerCase: 'initial'
				}
			}
		),
		jsonlint: {
			all: [
				'**/*.json',
				'!node_modules/**',
				'!vendor/**'
			]
		},
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

	grunt.registerTask( 'test', [ 'eslint:all', 'jsonlint', 'banana', 'jasmine_nodejs', 'stylelint' ] );
	grunt.registerTask( 'fix', 'eslint:fix' );
	grunt.registerTask( 'default', 'test' );
};
