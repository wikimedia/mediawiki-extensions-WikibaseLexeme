'use strict';

const { newStatementWithRandomStringValue } = require( './entityHelper' );
const {
	newAddLexemeFormRequestBuilder,
	newAddLexemeSenseRequestBuilder,
	newAddLexemeStatementRequestBuilder,
	newCreateLexemeRequestBuilder,
	newGetLexemeRequestBuilder
} = require( './RequestBuilderFactory' );

const withRequestInputs = ( requestInputs, newRequestBuilders ) => newRequestBuilders.map(
	( newRequestBuilder ) => ( { newRequestBuilder, requestInputs } )
);

const lexemeCreateRequests = ( requestInputs ) => withRequestInputs( requestInputs, [
	() => newCreateLexemeRequestBuilder( {
		lemmas: requestInputs.lemmas,
		language: requestInputs.language,
		lexical_category: requestInputs.lexicalCategory
	} )
] );

const lexemeGetRequests = ( requestInputs ) => withRequestInputs( requestInputs, [
	() => newGetLexemeRequestBuilder( requestInputs.lexemeId )
] );

const lexemeEditRequests = ( requestInputs ) => withRequestInputs( requestInputs, [
	() => newAddLexemeStatementRequestBuilder(
		requestInputs.lexemeId,
		newStatementWithRandomStringValue( requestInputs.statementPropertyId )
	),
	() => newAddLexemeFormRequestBuilder(
		requestInputs.lexemeId,
		{
			representations: { en: `potato-representation-${ Math.random() }` },
			grammatical_features: [ requestInputs.grammaticalFeatureId ]
		}
	)
] );

// move to lexemeEditRequests once finished
const addLexemeSenseRequest = ( requestInputs ) => ( {
	newRequestBuilder: () => newAddLexemeSenseRequestBuilder(
		requestInputs.lexemeId,
		{ glosses: { en: `potato-gloss-${ Math.random() }` } }
	),
	requestInputs
} );

module.exports = {
	lexemeCreateRequests,
	lexemeGetRequests,
	lexemeEditRequests,
	addLexemeSenseRequest
};
