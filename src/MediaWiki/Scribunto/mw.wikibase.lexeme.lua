--[[
	@license GPL-2.0-or-later
]]

local wikibaseLexeme = {}
local util = require 'libraryUtil'
local checkType = util.checkType

function wikibaseLexeme.setupInterface()
	local php = mw_interface
	mw_interface = nil

	function wikibaseLexeme.getLemmas( id )
		checkType( 'getLemmas', 1, id, 'string' )

		return php.getLemmas( id )
	end

	function wikibaseLexeme.getLanguage( id )
		checkType( 'getLanguage', 1, id, 'string' )

		return php.getLanguage( id )
	end

	function wikibaseLexeme.getLexicalCategory( id )
		checkType( 'getLexicalCategory', 1, id, 'string' )

		return php.getLexicalCategory( id )
	end

	wikibaseLexeme.splitLexemeId = function( id )
		checkType( 'splitLexemeId', 1, id, 'string' )

		local matches = { string.match( id, '^(L[1-9]%d*)-([SF][1-9]%d*)$') }
		if matches[1] ~= nil then
			return matches[1], matches[2]
		end
		return string.match( id, '^L[1-9]%d*$' )
	end

	mw = mw or {}
	mw.wikibase = mw.wikibase or {}
	mw.wikibase.lexeme = wikibaseLexeme
	package.loaded['mw.wikibase.lexeme'] = wikibaseLexeme
	wikibaseLexeme.setupInterface = nil
end

return wikibaseLexeme
