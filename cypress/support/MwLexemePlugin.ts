import { Util } from 'cypress-wikibase-api';
import CypressConfig from '../../cypress.config';
import Chainable = Cypress.Chainable;

const createLexeme = ( lexemeData, mwApiCommands ): Chainable<string> => {
	const lemma = Util.getTestString( 'lemma-' ),
		language = Util.getTestString( 'language-' ),
		lexicalCategory = Util.getTestString( 'lexicalCategory-' );

	return mwApiCommands[ 'MwApi:CreateItem' ]( { label: language } )
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
};

const addFormData = ( formData, mwApiCommands ): Chainable<string> => {
	const label = Util.getTestString( 'form-' );
	return mwApiCommands[ 'MwApi:CreateEntity' ]( {
		entityType: 'form',
		label: label,
		data: formData
	} );
};

export interface FormAndLexeme {
	formId: string;
	lexemeId: string;
}

export function mwApiLexemeCommands(
	cypressConfig: CypressConfig,
	mwApiCommands: Record<string, ( any ) => Chainable> ): Record<string, ( any ) => Chainable> {
	return {
		'MwLexemeApi:CreateLexeme'( lexemeData ): Chainable<string> {
			return createLexeme( lexemeData, mwApiCommands );
		},
		'MwLexemeApi:AddForm'( formData ): Chainable<string> {
			return addFormData( formData, mwApiCommands );
		},
		'MwLexemeApi:CreateLexemeWithForm'( representations ): Chainable<FormAndLexeme> {
			return createLexeme( null, mwApiCommands )
				.then( ( lexemeId ) => addFormData( {
					lexemeId,
					representations
				}, mwApiCommands )
					.then( ( formId ) => ( {
						lexemeId,
						formId
					} ) )
				);
		},
		'MwLexemeApi:AddSense'( { lexemeId, senseData } ): Chainable<string> {
			return mwApiCommands[ 'MwApi:BotRequest' ]( { isEdit: true, isPost: true, parameters: {
				action: 'wbladdsense',
				lexemeId: lexemeId,
				data: JSON.stringify( senseData )
			} } );
		}
	};
}
