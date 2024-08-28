import { Util } from 'cypress-wikibase-api';
import CypressConfig from '../../cypress.config';

export function mwApiLexemeCommands(
	cypressConfig: CypressConfig,
	mwApiCommands: Record<string, ( any ) => Chainable> ): Record<string, ( any ) => Chainable> {
	return {
		'MwLexemeApi:CreateLexeme'( lexemeData ): Chainable<string> {
			const lemma = Util.getTestString( 'lemma-' ),
				language = Util.getTestString( 'language-' ),
				lexicalCategory = Util.getTestString( 'lexicalCategory-' );

			return mwApiCommands[ 'MwApi:CreateItem' ]( { label: language } )
				// eslint-disable-next-line max-len
				.then( ( languageId ) => mwApiCommands[ 'MwApi:CreateItem' ]( { label: lexicalCategory } )
					.then( ( lexicalCategoryId ) => {
						const createData = {
							lexicalCategory: lexicalCategoryId,
							language: languageId,
							lemmas: {
								en: {
									value: 'color',
									language: 'en'
								}
							}
						};
						if ( lexemeData && lexemeData.lemmas ) {
							createData.lemmas = lexemeData.lemmas;
						}
						return mwApiCommands[ 'MwApi:CreateEntity' ]( {
							entityType: 'lexeme',
							label: lemma,
							data: createData
						} );
					} )
				);
		}
	};
}
