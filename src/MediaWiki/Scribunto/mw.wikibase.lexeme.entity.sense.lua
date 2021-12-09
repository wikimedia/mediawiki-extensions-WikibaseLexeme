--[[
	@license GPL-2.0-or-later
]]

local php = mw_interface
mw_interface = nil
local wikibaseLexemeEntitySense = {}
local methodtable = {}
local wikibaseEntity = require 'mw.wikibase.entity'
local util = require 'libraryUtil'
local checkType = util.checkType

function wikibaseLexemeEntitySense.create( data )
	if type( data ) ~= 'table' then
		error( 'Expected a table obtained via mw.wikibase.getEntity, got ' .. type( data ) .. ' instead' )
	end
	if next( data ) == nil then
		error( 'Expected a non-empty table obtained via mw.wikibase.getEntity' )
	end
	if type( data.id ) ~= 'string' then
		error( 'data.id must be a string, got ' .. type( data.id ) .. ' instead' )
	end

	data.schemaVersion = 2
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

function methodtable.getGlosses( entity )
	local glosses = {}
	for lang, gloss in pairs( entity.glosses ) do
		table.insert( glosses, { gloss.value, gloss.language } )
	end
	return glosses
end

function methodtable.getGloss( entity, language )
	checkType( 'getGloss', 1, language, 'string', true )
	language = language or mw.language.getContentLanguage():getCode()
	gloss = entity.glosses[ language ]
	if gloss then
		return gloss.value, gloss.language
	else
		return nil
	end
end

package.loaded['mw.wikibase.lexeme.entity.sense'] = wikibaseLexemeEntitySense

return wikibaseLexemeEntitySense
