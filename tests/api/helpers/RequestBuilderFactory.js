'use strict';

const { RequestBuilder } = require( './RequestBuilder' );

module.exports = {
	newGetLexemeRequestBuilder( lexemeId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/v0/entities/lexemes/{lexeme_id}' )
			.withPathParam( 'lexeme_id', lexemeId );
	},

	newCreateItemRequestBuilder( item ) {
		return new RequestBuilder()
			.withRoute( 'POST', '/v1/entities/items' )
			.withJsonBodyParam( 'item', item );
	},

	newCreatePropertyRequestBuilder( property ) {
		return new RequestBuilder()
			.withRoute( 'POST', '/v1/entities/properties' )
			.withJsonBodyParam( 'property', property );
	}
};
