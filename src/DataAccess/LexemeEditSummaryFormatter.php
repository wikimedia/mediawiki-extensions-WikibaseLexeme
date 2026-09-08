<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess;

use LogicException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lexeme\Domain\Model\AddStatementEditSummary;
use Wikibase\Lexeme\Domain\Model\CreateLexemeEditSummary;
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

		$lexemeSummary = $summary->editSummary;
		$formatterSummary = match ( true ) {
			$lexemeSummary instanceof CreateLexemeEditSummary => new Summary( 'wbeditentity', 'create-lexeme' ),
			$lexemeSummary instanceof AddStatementEditSummary => $this->newAddStatementSummary(
				$lexemeSummary->statement
			),
			default => throw new LogicException( 'Unknown summary type ' . get_class( $summary ) ),
		};
		$formatterSummary->setUserSummary( $lexemeSummary->getUserComment() );

		return $this->summaryFormatter->formatSummary( $formatterSummary );
	}

	private function newAddStatementSummary( Statement $statement ): Summary {
		$summary = new Summary( 'wbsetclaim', 'create' );
		$summary->addAutoSummaryArgs( [
			[ $statement->getPropertyId()->getSerialization() => $statement->getMainSnak() ],
		] );
		// the number of edited statements in wbsetclaim-related messages
		$summary->addAutoCommentArgs( 1 );

		return $summary;
	}

}
