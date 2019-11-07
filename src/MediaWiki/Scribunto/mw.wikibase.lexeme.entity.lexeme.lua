--[[
	@license GPL-2.0-or-later
]]

local php = mw_interface
mw_interface = nil
local wikibaseLexemeEntityLexeme = {}
local metatable = {}
local methodtable = {}
local util = require 'libraryUtil'
local checkType = util.checkType

metatable.__index = methodtable

wikibaseLexemeEntityLexeme.create = function( data )
	if type( data ) ~= 'table' then
		error( 'Expected a table obtained via mw.wikibase.getEntity, got ' .. type( data ) .. ' instead' )
	end
	if next( data ) == nil then
		error( 'Expected a non-empty table obtained via mw.wikibase.getEntity' )
	end
	if type( data.schemaVersion ) ~= 'number' then
		error( 'data.schemaVersion must be a number, got ' .. type( data.schemaVersion ) .. ' instead' )
	end
	if data.schemaVersion < 2 then
		error( 'mw.wikibase.entity must not be constructed using legacy data' )
	end
	if type( data.id ) ~= 'string' then
		error( 'data.id must be a string, got ' .. type( data.id ) .. ' instead' )
	end

	local entity = data
	php.addAllUsage( entity.id ) -- TODO support fine-grained usage tracking

	setmetatable( entity, metatable )
	return entity
end

methodtable.getId = function( entity )
	return entity.id
end

methodtable.getLanguage = function( entity )
	return entity.language
end

methodtable.getLexicalCategory = function( entity )
	return entity.lexicalCategory
end

package.loaded['mw.wikibase.lexeme.entity.lexeme'] = wikibaseLexemeEntityLexeme

return wikibaseLexemeEntityLexeme
