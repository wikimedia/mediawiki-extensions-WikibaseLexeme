<?php

namespace Wikibase\Lexeme\Diff;

use Diff\DiffOp\Diff\Diff;
use MessageLocalizer;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiff;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\BasicDiffView;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizer;

/**
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani
 */
class LexemeDiffVisualizer implements EntityDiffVisualizer {

	/**
	 * @var MessageLocalizer
	 */
	private $messageLocalizer;

	/**
	 * @var BasicEntityDiffVisualizer
	 */
	private $basicEntityDiffVisualizer;

	/**
	 * @var ClaimDiffer
	 */
	private $claimDiffer;

	/**
	 * @var ClaimDifferenceVisualizer
	 */
	private $claimDiffVisualizer;

	/**
	 * @var ItemReferenceDifferenceVisualizer
	 */
	private $itemReferenceDifferenceVisualizer;

	public function __construct(
		MessageLocalizer $messageLocalizer,
		EntityDiffVisualizer $basicEntityDiffVisualizer,
		ClaimDiffer $claimDiffer,
		ClaimDifferenceVisualizer $claimDiffView,
		ItemReferenceDifferenceVisualizer $itemReferenceDifferenceVisualizer
	) {
		$this->messageLocalizer = $messageLocalizer;
		$this->basicEntityDiffVisualizer = $basicEntityDiffVisualizer;
		$this->claimDiffer = $claimDiffer;
		$this->claimDiffVisualizer = $claimDiffView;
		$this->itemReferenceDifferenceVisualizer = $itemReferenceDifferenceVisualizer;
	}

	/**
	 * @param EntityContentDiff $diff
	 *
	 * @return string HTML
	 */
	public function visualizeEntityContentDiff( EntityContentDiff $diff ) {
		if ( $diff->isEmpty() ) {
			return '';
		}

		return $this->basicEntityDiffVisualizer->visualizeEntityContentDiff( $diff )
			. $this->visualizeEntityDiff( $diff->getEntityDiff() );
	}

	/**
	 * @param LexemeDiff $diff
	 *
	 * @return string HTML
	 */
	private function visualizeEntityDiff( LexemeDiff $diff ) {
		$basicDiffView = new BasicDiffView(
			[],
			new Diff(
				[
					$this->messageLocalizer->msg( 'wikibaselexeme-diffview-lemma' )->text() =>
						$diff->getLemmasDiff(),
				],
				true
			)
		);

		$lexicalCategoryDiff = $this->itemReferenceDifferenceVisualizer->visualize(
			$this->messageLocalizer->msg( 'wikibaselexeme-diffview-lexical-category' )->text(),
			$diff->getLexicalCategoryDiff()
		);

		$languageDiff = $this->itemReferenceDifferenceVisualizer->visualize(
			$this->messageLocalizer->msg( 'wikibaselexeme-diffview-language' )->text(),
			$diff->getLanguageDiff()
		);

		$formDiffView = new FormDiffView(
			[],
			new Diff(
				[
					$this->messageLocalizer->msg( 'wikibaselexeme-diffview-form' )->text() =>
						$diff->getFormsDiff(),
				],
				true
			),
			$this->claimDiffer,
			$this->claimDiffVisualizer,
			$this->itemReferenceDifferenceVisualizer,
			$this->messageLocalizer
		);

		$senseDiffView = new SenseDiffView(
			[],
			new Diff(
				[
					$this->messageLocalizer->msg( 'wikibaselexeme-diffview-sense' )->text() =>
						$diff->getSensesDiff(),
				],
				true
			),
			$this->claimDiffer,
			$this->claimDiffVisualizer,
			$this->messageLocalizer
		);

		return $basicDiffView->getHtml() .
			$lexicalCategoryDiff .
			$languageDiff .
			$formDiffView->getHtml() .
			$senseDiffView->getHtml();
	}

}
