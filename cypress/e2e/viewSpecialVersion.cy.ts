import { SpecialVersionPage } from '../support/pageObjects/SpecialVersionPage'

const specialVersionPage = new SpecialVersionPage()

describe('Special Version Page', () => {
	it('verifies that the WikibaseLexeme extension loads', () => {
		specialVersionPage.open().checkWikibaseLexemeExtensionLoaded()
	})
})
