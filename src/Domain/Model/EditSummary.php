<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
interface EditSummary {

	public function getUserComment(): ?string;

}
