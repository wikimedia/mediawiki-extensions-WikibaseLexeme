export class LexemePage {

	static LEMMA_WIDGET_SELECTORS = {
		LEMMA_LIST: '.lemma-widget_lemma-list'
	}

	static LEMMA_PAGE_SELECTORS = {
		HEADER_ID: '.wb-lexeme-header_id'
	}

	lemmaContainer() {
		cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.LEMMA_LIST );
		return this;
	}

	getHeaderId() {
		return cy.get( this.constructor.LEMMA_PAGE_SELECTORS.HEADER_ID ).then( ( element ) => {
			return element.text().replace( /[^L0-9]/g, '' );
		} );
	}

}
