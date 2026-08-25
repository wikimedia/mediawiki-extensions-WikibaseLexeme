<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess;

use LogicException;
use Wikibase\Lexeme\Domain\Model\EditSummaryAction;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditSummary;
use Wikibase\Repo\Domains\Crud\Infrastructure\EditSummaryFormatter;
use Wikibase\Repo\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class LexemeEditSummaryFormatter extends EditSummaryFormatter {

	public function __construct( private SummaryFormatter $summaryFormatter ) {
	}

	public function format( EditSummary $summary ): string {
		if ( !( $summary instanceof CrudEditSummaryAdapter ) ) {
			throw new LogicException( 'Unknown summary type ' . get_class( $summary ) );
		}

		$formatterSummary = match ( $summary->getEditAction() ) {
			EditSummaryAction::CREATE_LEXEME->name => new Summary( 'wbeditentity', 'create-lexeme' ),
		};
		$formatterSummary->setUserSummary( $summary->getUserComment() );

		return $this->summaryFormatter->formatSummary( $formatterSummary );
	}

}
