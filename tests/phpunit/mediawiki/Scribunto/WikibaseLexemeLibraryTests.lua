local testframework = require 'Module:TestFramework'

local tests = {
	{ name = 'splitLexemeId for sense ID',
	  func = mw.wikibase.lexeme.splitLexemeId,
	  args = { 'L123-S456' },
	  expect = { 'L123', 'S456' },
	},
	{ name = 'splitLexemeId for form ID',
	  func = mw.wikibase.lexeme.splitLexemeId,
	  args = { 'L123-F456' },
	  expect = { 'L123', 'F456' },
	},
	{ name = 'splitLexemeId for lexeme ID',
	  func = mw.wikibase.lexeme.splitLexemeId,
	  args = { 'L123' },
	  expect = { 'L123' },
	},
	{ name = 'splitLexemeId for item ID',
	  func = mw.wikibase.lexeme.splitLexemeId,
	  args = { 'Q123' },
	  expect = { nil },
	},
}

return testframework.getTestProvider( tests )
