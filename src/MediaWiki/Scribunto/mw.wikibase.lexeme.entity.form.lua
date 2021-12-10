--[[
	@license GPL-2.0-or-later
]]

local php = mw_interface
mw_interface = nil
local wikibaseLexemeEntityForm = {}
local methodtable = {}
local wikibaseEntity = require 'mw.wikibase.entity'
local util = require 'libraryUtil'
local checkType = util.checkType

function wikibaseLexemeEntityForm.create( data )
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

function methodtable.getRepresentations( entity )
	local representations = {}
	for lang, reprentation in pairs( entity.representations ) do
		table.insert( representations, { reprentation.value, reprentation.language } )
	end
	return representations
end

function methodtable.getRepresentation( entity, language )
	checkType( 'getRepresentation', 1, language, 'string', true )
	language = language or mw.language.getContentLanguage():getCode()
	representation = entity.representations[ language ]
	if representation then
		return representation.value, representation.language
	else
		return nil
	end
end

function methodtable.getGrammaticalFeatures( entity )
	return entity.grammaticalFeatures
end

function methodtable.hasGrammaticalFeature( entity, itemId )
	checkType( 'hasGrammaticalFeature', 1, itemId, 'string' )
	for i, grammaticalFeature in pairs( entity.grammaticalFeatures ) do
		if grammaticalFeature == itemId then
			return true
		end
	end
	return false
end

package.loaded['mw.wikibase.lexeme.entity.form'] = wikibaseLexemeEntityForm

return wikibaseLexemeEntityForm
