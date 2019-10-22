--[[
	@license GPL-2.0-or-later
]]

local wikibaseLexeme = {}
local util = require 'libraryUtil'
local checkType = util.checkType

function wikibaseLexeme.setupInterface()
	local php = mw_interface
	mw_interface = nil

	wikibaseLexeme.getLemmas = function( id )
		checkType( 'getLemmas', 1, id, 'string' )

		return php.getLemmas( id )
	end

	wikibaseLexeme.getLanguage = function( id )
		checkType( 'getLanguage', 1, id, 'string' )

		return php.getLanguage( id )
	end

	wikibaseLexeme.getLexicalCategory = function( id )
		checkType( 'getLexicalCategory', 1, id, 'string' )

		return php.getLexicalCategory( id )
	end

	mw = mw or {}
	mw.wikibase = mw.wikibase or {}
	mw.wikibase.lexeme = wikibaseLexeme
	package.loaded['mw.wikibase.lexeme'] = wikibaseLexeme
	wikibaseLexeme.setupInterface = nil
end

return wikibaseLexeme
