--[[
	@license GPL-2.0-or-later
]]

local php = mw_interface
mw_interface = nil
local wikibaseLexemeEntityLexeme = {}
local methodtable = {}
local wikibaseEntity = require 'mw.wikibase.entity'
local util = require 'libraryUtil'
local checkType = util.checkType

function wikibaseLexemeEntityLexeme.create( data )
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

function methodtable.getLemmas( entity )
	local lemmas = {}
	for lang, lemma in pairs( entity.lemmas ) do
		table.insert( lemmas, { lemma.value, lemma.language } )
	end
	return lemmas
end

function methodtable.getLemma( entity, language )
	checkType( 'getLemma', 1, language, 'string', true )
	language = language or mw.language.getContentLanguage():getCode()
	lemma = entity.lemmas[ language ]
	if lemma then
		return lemma.value, lemma.language
	else
		return nil
	end
end

function methodtable.getLanguage( entity )
	return entity.language
end

function methodtable.getLexicalCategory( entity )
	return entity.lexicalCategory
end

function methodtable.getForms( entity )
	local wikibaseLexemeEntityForm = require 'mw.wikibase.lexeme.entity.form'
	local forms = {}
	for i, form in pairs( entity.forms or {} ) do
		table.insert( forms, wikibaseLexemeEntityForm.create( form ) )
	end
	return forms
end

function methodtable.getSenses( entity )
	local wikibaseLexemeEntitySense = require 'mw.wikibase.lexeme.entity.sense'
	local senses = {}
	for i, sense in pairs( entity.senses or {} ) do
		table.insert( senses, wikibaseLexemeEntitySense.create( sense ) )
	end
	return senses
end

package.loaded['mw.wikibase.lexeme.entity.lexeme'] = wikibaseLexemeEntityLexeme

return wikibaseLexemeEntityLexeme
