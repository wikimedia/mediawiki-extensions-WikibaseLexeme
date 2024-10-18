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
	{ name = 'getRepresentation of existing Form with en-gb',
	  func = getFormAsEntityAndCallMethod,
	  args = { 'L1-F1', 'getRepresentation', { 'en-gb' } },
	  expect = { 'British English representation', 'en-gb' },
	},
	{ name = 'getRepresentation of existing Form with de',
	  func = getFormAsEntityAndCallMethod,
	  args = { 'L1-F1', 'getRepresentation', { 'de' } },
	  expect = { nil },
	},
	{ name = 'getRepresentation of existing Form with content language',
	  func = getFormAsEntityAndCallMethod,
	  args = { 'L1-F1', 'getRepresentation', {} },
	  expect = { 'English representation', 'en' },
	},
	{ name = 'getRepresentation of existing Form with bad language argument',
	  func = getFormAsEntityAndCallMethod,
	  args = { 'L1-F1', 'getRepresentation', { 1 } },
	  expect = "bad argument #1 to 'getRepresentation' (string expected, got number)",
	},
	{ name = 'getGrammaticalFeatures of existing Form',
	  func = getFormAsEntityAndCallMethod,
	  args = { 'L1-F1', 'getGrammaticalFeatures' },
	  expect = { { 'Q1' } },
	},
	{ name = 'hasGrammaticalFeature of existing Form with present grammatical feature',
	  func = getFormAsEntityAndCallMethod,
	  args = { 'L1-F1', 'hasGrammaticalFeature', { 'Q1' } },
	  expect = { true },
	},
	{ name = 'hasGrammaticalFeature of existing Form with absent grammatical feature',
	  func = getFormAsEntityAndCallMethod,
	  args = { 'L1-F1', 'hasGrammaticalFeature', { 'Q2' } },
	  expect = { false },
	},
	{ name = 'hasGrammaticalFeature of existing Form with bad grammatical feature argument',
	  func = getFormAsEntityAndCallMethod,
	  args = { 'L1-F1', 'hasGrammaticalFeature', { 1 } },
	  expect = "bad argument #1 to 'hasGrammaticalFeature' (string expected, got number)",
	},
	{ name = 'hasGrammaticalFeature of existing Form with missing grammatical feature argument',
	  func = getFormAsEntityAndCallMethod,
	  args = { 'L1-F1', 'hasGrammaticalFeature', {} },
	  expect = "bad argument #1 to 'hasGrammaticalFeature' (string expected, got nil)",
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
