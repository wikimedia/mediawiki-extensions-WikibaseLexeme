<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess;

use Wikibase\Lexeme\Domain\Model\EditSummaryAction;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditSummary;

/**
 * @license GPL-2.0-or-later
 */
class CrudEditSummaryAdapter implements EditSummary {

	public function __construct( private readonly EditSummaryAction $editSummaryAction ) {
	}

	public function getEditAction(): string {
		return $this->editSummaryAction->name;
	}

	public function getUserComment(): ?string {
		return null; // T434439
	}

}
