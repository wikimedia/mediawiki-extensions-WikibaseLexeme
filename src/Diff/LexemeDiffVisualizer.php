<?php

namespace Wikibase\Lexeme\Diff;

use Diff\DiffOp\Diff\Diff;
use MessageLocalizer;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\BasicDiffView;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizer;

/**
 * Class for generating views of EntityDiff objects.
 *
 * @license GPL-2.0+
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
	 * @var ClaimDiffer|null
	 */
	private $claimDiffer;

	/**
	 * @var ClaimDifferenceVisualizer|null
	 */
	private $claimDiffVisualizer;

	public function __construct(
		MessageLocalizer $messageLocalizer,
		EntityDiffVisualizer $basicEntityDiffVisualizer,
		ClaimDiffer $claimDiffer,
		ClaimDifferenceVisualizer $claimDiffView
	) {
		$this->messageLocalizer = $messageLocalizer;
		$this->basicEntityDiffVisualizer = $basicEntityDiffVisualizer;
		$this->claimDiffer = $claimDiffer;
		$this->claimDiffVisualizer = $claimDiffView;
	}

	/**
	 * Generates and returns an HTML visualization of the provided EntityContentDiff.
	 *
	 * @param EntityContentDiff $diff
	 *
	 * @return string
	 */
	public function visualizeEntityContentDiff( EntityContentDiff $diff ) {
		if ( $diff->isEmpty() ) {
			return '';
		}

		$basicHtml = $this->basicEntityDiffVisualizer->visualizeEntityContentDiff( $diff );

		return $basicHtml . $this->visualizeEntityDiff( $diff->getEntityDiff() );
	}

	/**
	 * Generates and returns an HTML visualization of the provided EntityDiff.
	 *
	 * @param EntityDiff $diff
	 *
	 * @return string
	 */
	private function visualizeEntityDiff( EntityDiff $diff ) {
		$basicDiff = ( new BasicDiffView(
			[],
			new Diff(
				[
					$this->messageLocalizer->msg( 'wikibaselexeme-diffview-lemma' )->text() =>
						$diff->getLemmasDiff(),
					$this->messageLocalizer->msg( 'wikibaselexeme-diffview-lexical-category' )
						->text() => $diff->getLexicalCategoryDiff(),
					$this->messageLocalizer->msg( 'wikibaselexeme-diffview-language' )->text() =>
						$diff->getLanguageDiff()
				],
				true
			)
		) )->getHtml();
		$formDiff = ( new FormDiffView(
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
		) )->getHtml();
		return $basicDiff . $formDiff;
	}

}
