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
