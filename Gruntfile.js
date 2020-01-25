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
				extensions: [ '.js', '.json' ],
				cache: true
			},
			all: [
				'**/*.{js,json}',
				'!Gruntfile.js',
				'!node_modules/**',
				'!resources/vendor/**',
				'!vendor/**'
			],
			fix: {
				options: {
					extensions: [ '.js', '.json' ],
					fix: true
				},
				src: [
					'**/*.{js,json}',
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
	grunt.registerTask( 'fix', 'eslint:fix' );
	grunt.registerTask( 'default', 'test' );
};
