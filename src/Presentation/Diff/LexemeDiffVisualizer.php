<?php

namespace Wikibase\Lexeme\Presentation\Diff;

use Diff\DiffOp\Diff\Diff;
use MessageLocalizer;
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

		return $this->visualizeEntityDiff( $diff );
	}

	/**
	 * @return string HTML
	 * @suppress PhanUndeclaredMethod
	 */
	private function visualizeEntityDiff( EntityContentDiff $entityContentDiff ) {
		$lexemeDiff = $entityContentDiff->getEntityDiff();

		$lemmaDiffView = new BasicDiffView(
			[],
			new Diff(
				[
					$this->messageLocalizer->msg( 'wikibaselexeme-diffview-lemma' )->text() =>
						$lexemeDiff->getLemmasDiff(),
				],
				true
			)
		);

		$lexicalCategoryDiffHTML = $this->itemReferenceDifferenceVisualizer->visualize(
			$this->messageLocalizer->msg( 'wikibaselexeme-diffview-lexical-category' )->text(),
			$lexemeDiff->getLexicalCategoryDiff()
		);

		$languageDiffHTML = $this->itemReferenceDifferenceVisualizer->visualize(
			$this->messageLocalizer->msg( 'wikibaselexeme-diffview-language' )->text(),
			$lexemeDiff->getLanguageDiff()
		);

		$lexemeStatementDiffHTML = $this->basicEntityDiffVisualizer
			->visualizeEntityContentDiff( $entityContentDiff );

		$formDiffView = new FormDiffView(
			[],
			new Diff(
				[
					$this->messageLocalizer->msg( 'wikibaselexeme-diffview-form' )->text() =>
						$lexemeDiff->getFormsDiff(),
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
						$lexemeDiff->getSensesDiff(),
				],
				true
			),
			$this->claimDiffer,
			$this->claimDiffVisualizer,
			$this->messageLocalizer
		);

		return $lemmaDiffView->getHtml() .
			$lexicalCategoryDiffHTML .
			$languageDiffHTML .
			$lexemeStatementDiffHTML .
			$senseDiffView->getHtml() .
			$formDiffView->getHtml();
	}

}
