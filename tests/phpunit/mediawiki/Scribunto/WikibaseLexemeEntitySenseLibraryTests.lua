local testframework = require 'Module:TestFramework'

local function getSenseAsEntityAndCallMethod( senseid, name, args )
	local sense = mw.wikibase.getEntity( senseid )
	return sense[ name ]( sense, unpack( args or {} ) )
end

local function getSenseFromLexemeAndCallMethod( senseid, name, args )
	local lexemeid = mw.wikibase.lexeme.splitLexemeId( senseid )
	local lexeme = mw.wikibase.getEntity( lexemeid )
	for i, sense in pairs( lexeme:getSenses() ) do
		if sense.id == senseid then
			return sense[ name ]( sense, unpack( args or {} ) )
		end
	end
	error( 'Sense not found' )
end

local tests = {
	{ name = 'getGlosses of existing Sense',
	  func = getSenseAsEntityAndCallMethod,
	  args = { 'L1-S1', 'getGlosses' },
	  expect = { { { 'English gloss', 'en' }, { 'British English gloss', 'en-gb' } } },
	},
	{ name = 'getGloss of existing Sense with en-gb',
	  func = getSenseAsEntityAndCallMethod,
	  args = { 'L1-S1', 'getGloss', { 'en-gb' } },
	  expect = { 'British English gloss', 'en-gb' },
	},
	{ name = 'getGloss of existing Sense with de',
	  func = getSenseAsEntityAndCallMethod,
	  args = { 'L1-S1', 'getGloss', { 'de' } },
	  expect = { nil },
	},
	{ name = 'getGloss of existing Sense with content language',
	  func = getSenseAsEntityAndCallMethod,
	  args = { 'L1-S1', 'getGloss', {} },
	  expect = { 'English gloss', 'en' },
	},
	{ name = 'getGloss of existing Sense with bad language argument',
	  func = getSenseAsEntityAndCallMethod,
	  args = { 'L1-S1', 'getGloss', { 1 } },
	  expect = "bad argument #1 to 'getGloss' (string expected, got number)",
	},
	{ name = 'getId of existing Sense',
	  func = getSenseAsEntityAndCallMethod,
	  args = { 'L1-S1', 'getId' },
	  expect = { 'L1-S1' },
	},
	{ name = 'getGlosses of existing Sense (from Lexeme)',
	  func = getSenseFromLexemeAndCallMethod,
	  args = { 'L1-S1', 'getGlosses' },
	  expect = { { { 'English gloss', 'en' }, { 'British English gloss', 'en-gb' } } },
	},
	{ name = 'getId of existing Sense (from Lexeme)',
	  func = getSenseFromLexemeAndCallMethod,
	  args = { 'L1-S1', 'getId' },
	  expect = { 'L1-S1' },
	},
}

return testframework.getTestProvider( tests )
