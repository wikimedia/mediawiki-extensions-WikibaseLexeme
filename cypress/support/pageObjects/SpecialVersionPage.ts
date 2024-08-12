export class SpecialVersionPage {
	open() {
		cy.visit( 'index.php?title=Special:Version' )
		return this
	}

	checkWikibaseLexemeExtensionLoaded() {
		cy.get('#mw-version-ext-wikibase-WikibaseLexeme')
		return this
	}
}
