'use strict';

const { RequestBuilder } = require( './RequestBuilder' );

module.exports = {
	newGetLexemeRequestBuilder( lexemeId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/wikibaselexeme/v0/entities/lexemes/{lexeme_id}' )
			.withPathParam( 'lexeme_id', lexemeId );
	},

	newCreateLexemeRequestBuilder( lexeme ) {
		return new RequestBuilder()
			.withRoute( 'POST', '/wikibaselexeme/v0/entities/lexemes' )
			.withJsonBodyParam( 'lexeme', lexeme );
	},

	newAddLexemeStatementRequestBuilder( lexemeId, statement ) {
		return new RequestBuilder()
			.withRoute( 'POST', '/wikibaselexeme/v0/entities/lexemes/{lexeme_id}/statements' )
			.withPathParam( 'lexeme_id', lexemeId )
			.withJsonBodyParam( 'statement', statement );
	},

	newAddLexemeFormRequestBuilder( lexemeId, form ) {
		return new RequestBuilder()
			.withRoute( 'POST', '/wikibaselexeme/v0/entities/lexemes/{lexeme_id}/forms' )
			.withPathParam( 'lexeme_id', lexemeId )
			.withJsonBodyParam( 'form', form );
	},

	newCreateItemRequestBuilder( item ) {
		return new RequestBuilder()
			.withRoute( 'POST', '/wikibase/v1/entities/items' )
			.withJsonBodyParam( 'item', item );
	},

	newCreatePropertyRequestBuilder( property ) {
		return new RequestBuilder()
			.withRoute( 'POST', '/wikibase/v1/entities/properties' )
			.withJsonBodyParam( 'property', property );
	}
};
