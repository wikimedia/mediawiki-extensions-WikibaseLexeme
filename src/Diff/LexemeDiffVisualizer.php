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
	private $lexicalCategoryDifferenceVisualizer;

	public function __construct(
		MessageLocalizer $messageLocalizer,
		EntityDiffVisualizer $basicEntityDiffVisualizer,
		ClaimDiffer $claimDiffer,
		ClaimDifferenceVisualizer $claimDiffView,
		ItemReferenceDifferenceVisualizer $lexicalCategoryDifferenceVisualizer
	) {
		$this->messageLocalizer = $messageLocalizer;
		$this->basicEntityDiffVisualizer = $basicEntityDiffVisualizer;
		$this->claimDiffer = $claimDiffer;
		$this->claimDiffVisualizer = $claimDiffView;
		$this->lexicalCategoryDifferenceVisualizer = $lexicalCategoryDifferenceVisualizer;
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
					$this->messageLocalizer->msg( 'wikibaselexeme-diffview-language' )->text() =>
						$diff->getLanguageDiff()
				],
				true
			)
		);

		$lexicalCategoryDiff = $this->lexicalCategoryDifferenceVisualizer->visualize(
			$this->messageLocalizer->msg( 'wikibaselexeme-diffview-lexical-category' )->text(),
			$diff->getLexicalCategoryDiff()
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
			$this->messageLocalizer
		);

		return $basicDiffView->getHtml() . $lexicalCategoryDiff . $formDiffView->getHtml();
	}

}
