<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class CreateLexemeEditSummary implements EditSummary {

	public function __construct( private readonly ?string $userComment ) {
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

}
