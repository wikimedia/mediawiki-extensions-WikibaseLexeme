( function () {
	'use strict';
	var LexemeDeserializer = require( './serialization/LexemeDeserializer.js' );

	module.exports = function () {
		return new LexemeDeserializer();
	};
}() );
