local testframework = require 'Module:TestFramework'

local function getEntityAndCallMethod( id, name, args )
	local entity = mw.wikibase.getEntity( id )
	return entity[ name ]( entity, unpack( args or {} ) )
end

local tests = {
	{ name = 'getLemmas of existing Lexeme',
	  func = getEntityAndCallMethod,
	  args = { 'L1', 'getLemmas' },
	  expect = { { { 'English lemma', 'en' }, { 'British English lemma', 'en-gb' } } },
	},
	{ name = 'getLemma of existing Lexeme with en-gb',
	  func = getEntityAndCallMethod,
	  args = { 'L1', 'getLemma', { 'en-gb' } },
	  expect = { 'British English lemma', 'en-gb' },
	},
	{ name = 'getLemma of existing Lexeme with de',
	  func = getEntityAndCallMethod,
	  args = { 'L1', 'getLemma', { 'de' } },
	  expect = { nil },
	},
	{ name = 'getLemma of existing Lexeme with content language',
	  func = getEntityAndCallMethod,
	  args = { 'L1', 'getLemma', {} },
	  expect = { 'English lemma', 'en' },
	},
	{ name = 'getLemma of existing Lexeme with bad language argument',
	  func = getEntityAndCallMethod,
	  args = { 'L1', 'getLemma', { 1 } },
	  expect = "bad argument #1 to 'getLemma' (string expected, got number)",
	},
	{ name = 'getLanguage of existing Lexeme',
	  func = getEntityAndCallMethod,
	  args = { 'L1', 'getLanguage' },
	  expect = { 'Q1' },
	},
	{ name = 'getLexicalCategory of existing Lexeme',
	  func = getEntityAndCallMethod,
	  args = { 'L1', 'getLexicalCategory' },
	  expect = { 'Q2' },
	},
	{ name = 'getId of existing Lexeme',
	  func = getEntityAndCallMethod,
	  args = { 'L1', 'getId' },
	  expect = { 'L1' },
	},
}

return testframework.getTestProvider( tests )
