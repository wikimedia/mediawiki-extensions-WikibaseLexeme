<?php

namespace Wikibase\Lexeme\Domain\EntityReferenceExtractors;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\FormSet;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractor;
use Wikimedia\Assert\Assert;

/**
 * Extracts the item ids of a lexeme's forms' grammatical features
 *
 * @license GPL-2.0-or-later
 */
class GrammaticalFeatureItemIdsExtractor implements EntityReferenceExtractor {

	/**
	 * @param EntityDocument $lexeme
	 * @return ItemId[]
	 */
	public function extractEntityIds( EntityDocument $lexeme ) {
		Assert::parameterType( Lexeme::class, $lexeme, '$lexeme' );
		'@phan-var Lexeme $lexeme';

		/** @var Lexeme $lexeme */
		return $this->extractGrammaticalFeatureIds( $lexeme->getForms() );
	}

	/**
	 * @param FormSet $forms
	 * @return ItemId[]
	 */
	private function extractGrammaticalFeatureIds( FormSet $forms ) {
		$ids = [];

		foreach ( $forms->toArrayUnordered() as $form ) {
			$ids = array_merge( $ids, $form->getGrammaticalFeatures() );
		}

		return array_values( array_unique( $ids ) );
	}

}
