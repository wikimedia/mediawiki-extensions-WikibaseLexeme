<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess;

use Wikibase\Lexeme\Domain\Model\EditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditSummary as CrudEditSummary;

/**
 * @license GPL-2.0-or-later
 */
class CrudEditSummaryAdapter implements CrudEditSummary {

	public function __construct( public readonly EditSummary $editSummary ) {
	}

	public function getEditAction(): string {
		// only read by EditSummaryFormatter, which is replaced by LexemeEditSummaryFormatter
		return __METHOD__ . ' not expected to be called';
	}

	public function getUserComment(): ?string {
		return $this->editSummary->getUserComment();
	}

}
