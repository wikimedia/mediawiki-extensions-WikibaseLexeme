<?php
namespace Wikibase\Lexeme\DataAccess\Search;

use CirrusSearch\Search\Result;
use Html;
use HtmlArmor;
use Language;

/**
 * Single CirrusSearch result for Lexeme fulltext search.
 */
class LexemeResult extends Result {
	/**
	 * Raw label data from source.
	 * @var string
	 */
	private $labelData;
	/**
	 * Description data with highlighting.
	 * @var string
	 */
	private $descriptionData;
	/**
	 * Original source data
	 * @var array
	 */
	private $sourceData;
	/**
	 * @var bool
	 */
	private $isFormResult;

	/**
	 * @param Language $displayLanguage
	 * @param LexemeDescription $descriptionMaker
	 * @param array $result Result from LexemeFulltextResult
	 * @throws \MWException
	 */
	public function __construct(
		Language $displayLanguage,
		LexemeDescription $descriptionMaker,
		array $result
	) {
		// Let parent Result class handle the boring stuff
		parent::__construct( null, $result['elasticResult'] );
		$this->sourceData = $result['elasticResult']->getSource();
		$this->isFormResult = isset( $result['formId'] );
		if ( $this->isFormResult ) {
			// Form
			$this->descriptionData = $descriptionMaker->createFormDescription( $result['lexemeId'],
				$result['features'], $result['lemma'], $result['lang'],
				$result['category'] );
			$this->labelData = $result['representation'];
			// This copies FormTitleStoreLookup, we could instantiate one instead
			// but that would add a lot of wrapper code.
			$this->mTitle->setFragment( '#' . $result['formId'] );
		} else {
			// Lexeme
			$this->descriptionData = $descriptionMaker->createDescription( $result['lexemeId'],
				$result['lang'], $result['category'] );
			$this->labelData = $result['lemma'];
		}
	}

	/**
	 * @return string
	 */
	public function getTitleSnippet() {
		return HtmlArmor::getHtml( $this->labelData );
	}

	/**
	 * @param array $terms
	 * @return string
	 */
	public function getTextSnippet( $terms ) {
		$attr = [ 'class' => 'wb-itemlink-description' ];
		return Html::rawElement( 'span', $attr, HtmlArmor::getHtml( $this->descriptionData ) );
	}

	/**
	 * Get number of statements
	 * @return int
	 */
	public function getStatementCount() {
		if ( !isset( $this->sourceData['statement_count'] ) ) {
			return 0;
		}
		return (int)$this->sourceData['statement_count'];
	}

	/**
	 * Get number of statements
	 * @return int
	 */
	public function getFormCount() {
		if ( empty( $this->sourceData[FormsField::NAME] ) ) {
			return 0;
		}
		return count( $this->sourceData[FormsField::NAME] );
	}

	/**
	 * Is this Form result?
	 * @return bool
	 */
	public function isFormResult() {
		return $this->isFormResult;
	}

}
