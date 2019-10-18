local testframework = require 'Module:TestFramework'

local function getEntityAndCallMethod( id, name, args )
	local entity = mw.wikibase.getEntity( id )
	return entity[ name ]( entity, unpack( args or {} ) )
end

local tests = {
	{ name = 'getLanguage of existing lexeme',
	  func = getEntityAndCallMethod,
	  args = { 'L1', 'getLanguage' },
	  expect = { 'Q1' },
	},
	{ name = 'getLexicalCategory of existing lexeme',
	  func = getEntityAndCallMethod,
	  args = { 'L1', 'getLexicalCategory' },
	  expect = { 'Q2' },
	},
	{ name = 'getId of existing lexeme',
	  func = getEntityAndCallMethod,
	  args = { 'L1', 'getId' },
	  expect = { 'L1' },
	},
}

return testframework.getTestProvider( tests )
