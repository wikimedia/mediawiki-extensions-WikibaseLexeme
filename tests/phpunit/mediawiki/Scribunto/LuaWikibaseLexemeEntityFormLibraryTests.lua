local testframework = require 'Module:TestFramework'

local function getFormAsEntityAndCallMethod( formid, name, args )
	local form = mw.wikibase.getEntity( formid )
	return form[ name ]( form, unpack( args or {} ) )
end

local function getFormFromLexemeAndCallMethod( formid, name, args )
	local lexemeid = mw.wikibase.lexeme.splitLexemeId( formid )
	local lexeme = mw.wikibase.getEntity( lexemeid )
	for i, form in pairs( lexeme:getForms() ) do
		if form.id == formid then
			return form[ name ]( form, unpack( args or {} ) )
		end
	end
	error( 'Form not found' )
end

local tests = {
	{ name = 'getRepresentations of existing Form',
	  func = getFormAsEntityAndCallMethod,
	  args = { 'L1-F1', 'getRepresentations' },
	  expect = { { { 'English representation', 'en' }, { 'British English representation', 'en-gb' } } },
	},
	{ name = 'getGrammaticalFeatures of existing Form',
	  func = getFormAsEntityAndCallMethod,
	  args = { 'L1-F1', 'getGrammaticalFeatures' },
	  expect = { { 'Q1' } },
	},
	{ name = 'getId of existing Form',
	  func = getFormAsEntityAndCallMethod,
	  args = { 'L1-F1', 'getId' },
	  expect = { 'L1-F1' },
	},
	{ name = 'getRepresentations of existing Form (from Lexeme)',
	  func = getFormFromLexemeAndCallMethod,
	  args = { 'L1-F1', 'getRepresentations' },
	  expect = { { { 'English representation', 'en' }, { 'British English representation', 'en-gb' } } },
	},
	{ name = 'getId of existing Form',
	  func = getFormFromLexemeAndCallMethod,
	  args = { 'L1-F1', 'getId' },
	  expect = { 'L1-F1' },
	},
}

return testframework.getTestProvider( tests )
