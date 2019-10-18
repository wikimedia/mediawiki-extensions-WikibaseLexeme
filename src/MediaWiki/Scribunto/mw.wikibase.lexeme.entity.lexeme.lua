--[[
	@license GPL-2.0-or-later
]]

local php = mw_interface
mw_interface = nil
local wikibaseLexemeEntityLexeme = {}
local methodtable = {}
local wikibaseEntity = require 'mw.wikibase.entity'

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

	local entity = wikibaseEntity.create( data )
	php.addAllUsage( entity.id ) -- TODO support fine-grained usage tracking

	-- preserve original methods (ensuring function form even if __index was a table)
	local originalmethods = getmetatable( entity ).__index
	if type( originalmethods ) == 'nil' then
		originalmethods = {}
	end
	if type( originalmethods ) == 'table' then
		local oldoriginalmethods = originalmethods
		originalmethods = function( table, key )
			return oldoriginalmethods[key]
		end
	end

	-- build metatable that searches our methods first and falls back to the original ones
	local metatable = {}
	metatable.__index = function( table, key )
		local ourmethod = methodtable[key]
		if ourmethod ~= nil then
			return ourmethod
		end
		return originalmethods( table, key )
	end

	setmetatable( entity, metatable )
	return entity
end

methodtable.getLanguage = function( entity )
	return entity.language
end

methodtable.getLexicalCategory = function( entity )
	return entity.lexicalCategory
end

package.loaded['mw.wikibase.lexeme.entity.lexeme'] = wikibaseLexemeEntityLexeme

return wikibaseLexemeEntityLexeme
