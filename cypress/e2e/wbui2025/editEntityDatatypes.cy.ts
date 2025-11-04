import { Util } from 'cypress-wikibase-api';

import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';

describe( 'wbui2025 Lexeme entityId datatypes (lexeme, form, sense)', () => {

	let languageItemId: string;
	let lexicalCategoryItemId: string;
	let parentLexemeId: string;

	before( () => {
		cy.task( 'MwApi:CreateItem', {
			label: Util.getTestString( 'language-' ),
			data: { claims: [] }
		} ).then( ( newItemId: string ) => {
			languageItemId = newItemId;
			cy.task( 'MwApi:CreateItem', {
				label: Util.getTestString( 'lexical-category-' ),
				data: { claims: [] }
			} ).then( ( newCategoryItemId: string ) => {
				lexicalCategoryItemId = newCategoryItemId;
				cy.task( 'MwApi:CreateEntity', {
					entityType: 'lexeme',
					data: {
						lemmas: {
							en: { language: 'en', value: Util.getTestString( 'parent-lexeme-' ) }
						},
						language: languageItemId,
						lexicalCategory: lexicalCategoryItemId,
						claims: []
					}
				} ).then( ( newLexemeId: string ) => {
					parentLexemeId = newLexemeId;
				} );
			} );
		} );
	} );

	const createEntityForDatatype = {
		lexeme: ( label: string ) => cy.task( 'MwApi:CreateEntity', {
			entityType: 'lexeme',
			label: label,
			data: {
				lemmas: { en: { language: 'en', value: label } },
				language: languageItemId,
				lexicalCategory: lexicalCategoryItemId,
				claims: []
			}
		} ),
		sense: ( label: string ) => cy.task( 'MwApi:AddSense', {
			lexemeId: parentLexemeId,
			data: {
				glosses: { en: { language: 'en', value: label } }
			}
		} ),
		form: ( label: string ) => cy.task( 'MwApi:AddForm', {
			lexemeId: parentLexemeId,
			data: {
				representations: { en: { language: 'en', value: label } },
				grammaticalFeatures: []
			}
		} )
	};

	for ( const datatype of [ 'lexeme', 'sense', 'form' ] ) {
		context( 'mobile view - ' + datatype + ' datatype', () => {
			let propertyName: string;
			let entityId: string;
			let linkedEntityId: string;
			let linkedEntityLabel: string;
			let newLinkedEntityLabel: string;
			let newLinkedEntityId: string;

			before( () => {
				propertyName = Util.getTestString( datatype + '-property-' );
				linkedEntityLabel = Util.getTestString( 'linked-' + datatype + '-' );
				newLinkedEntityLabel = Util.getTestString( 'new-linked-' + datatype + '-' );
				createEntityForDatatype[ datatype ]( linkedEntityLabel )
					.then( ( newEntityId: string ) => {
						linkedEntityId = newEntityId;
					} );
				createEntityForDatatype[ datatype ]( newLinkedEntityLabel )
					.then( ( newEntityId: string ) => {
						newLinkedEntityId = newEntityId;
					} );
				cy.task( 'MwApi:CreateProperty', {
					label: propertyName,
					data: { datatype: 'wikibase-' + datatype }
				} ).then( ( newPropertyId: string ) => {
					const statementData = {
						claims: [ {
							mainsnak: {
								snaktype: 'value',
								property: newPropertyId,
								datavalue: {
									value: {
										'entity-type': datatype,
										id: linkedEntityId
									},
									type: 'wikibase-entityid'
								},
								datatype: 'wikibase-' + datatype
							},
							type: 'statement',
							rank: 'normal'
						} ]
					};
					cy.task( 'MwApi:CreateItem', {
						label: Util.getTestString( 'item-with-' + datatype + '-statement' ),
						data: statementData
					} ).then( ( newItemId: string ) => {
						entityId = newItemId;
					} );
				} );
			} );

			beforeEach( () => {
				cy.viewport( 375, 1280 );
			} );

			it( 'displays item statement and supports full editing workflow', () => {
				const itemViewPage = new ItemViewPage( entityId );
				itemViewPage.open().statementsSection();

				itemViewPage.editLinks().first().should( 'exist' ).should( 'be.visible' );
				itemViewPage.editLinks().first().click();

				const editFormPage = new EditStatementFormPage();
				editFormPage.formHeading().should( 'exist' );
				editFormPage.propertyName().should( 'have.text', propertyName );

				editFormPage.lookupComponent()
					.should( 'exist' ).should( 'be.visible' );

				editFormPage.lookupInput()
					.should( 'have.value', linkedEntityId );

				editFormPage.lookupInput().clear();
				editFormPage.lookupInput().type( newLinkedEntityId );
				editFormPage.lookupInput().focus();

				editFormPage.menu().should( 'be.visible' );

				editFormPage.menuItems().first().click();
				editFormPage.lookupInput().should(
					'have.value',
					( datatype === 'sense' ? newLinkedEntityLabel : newLinkedEntityId )
				);

				editFormPage.publishButton().click();

				/* Wait for the form to close, and check the value is changed */
				editFormPage.formHeading().should( 'not.exist' );
				itemViewPage.mainSnakValues().first().should(
					'contain.text', newLinkedEntityLabel
				);

			} );
		} );
	}
} );
