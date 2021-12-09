--[[
	@license GPL-2.0-or-later
]]

local wikibaseLexeme = {}
local util = require 'libraryUtil'
local checkType = util.checkType

function wikibaseLexeme.setupInterface()
	mw_interface = nil -- currently unused, see git history or other modules for example usage

	wikibaseLexeme.splitLexemeId = function( id )
		checkType( 'splitLexemeId', 1, id, 'string' )

		local matches = { string.match( id, '^(L[1-9]%d*)-([SF][1-9]%d*)$') }
		if matches[1] ~= nil then
			return matches[1], matches[2]
		end
		return string.match( id, '^L[1-9]%d*$' )
	end

	local wikibase = require 'mw.wikibase'
	wikibase.lexeme = wikibaseLexeme
	package.loaded['mw.wikibase.lexeme'] = wikibaseLexeme
	wikibaseLexeme.setupInterface = nil
end

return wikibaseLexeme
