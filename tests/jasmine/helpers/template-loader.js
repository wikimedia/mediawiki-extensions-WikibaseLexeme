module.exports = function( rootPath ){
	var path = require( 'path' );
	var fs = require( 'fs' );

	var templatePath = path.resolve( __dirname, '../../..', rootPath );
	var template = fs.readFileSync( templatePath, 'utf8' );
	return template;
}
