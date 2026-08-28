<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
enum EditSummaryAction {

	case CREATE_LEXEME;
	case ADD_STATEMENT;

}
