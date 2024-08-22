export class SpecialVersionPage {
	public open(): this {
		cy.visit( 'index.php?title=Special:Version' );
		return this;
	}

	public checkWikibaseLexemeExtensionLoaded(): this {
		cy.get( '#mw-version-ext-wikibase-WikibaseLexeme' );
		return this;
	}
}
