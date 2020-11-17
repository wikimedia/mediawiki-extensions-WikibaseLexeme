<?php

namespace Wikibase\Lexeme\Domain\EntityReferenceExtractors;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractor;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikimedia\Assert\Assert;

/**
 * Extracts the referenced entity ids of each lexeme's senses' statements
 *
 * @license GPL-2.0-or-later
 */
class SensesStatementEntityReferenceExtractor implements EntityReferenceExtractor {

	/**
	 * @var StatementEntityReferenceExtractor
	 */
	private $statementEntityReferenceExtractor;

	public function __construct(
		StatementEntityReferenceExtractor $statementEntityReferenceExtractor
	) {
		$this->statementEntityReferenceExtractor = $statementEntityReferenceExtractor;
	}

	/**
	 * @param EntityDocument $lexeme
	 * @return EntityId[]
	 */
	public function extractEntityIds( EntityDocument $lexeme ) {
		Assert::parameterType( Lexeme::class, $lexeme, '$lexeme' );
		'@phan-var Lexeme $lexeme';

		$ids = [];

		/** @var Lexeme $lexeme */
		foreach ( $lexeme->getSenses()->toArrayUnordered() as $sense ) {
			$ids = array_merge(
				$ids,
				$this->statementEntityReferenceExtractor->extractEntityIds( $sense )
			);
		}

		return array_unique( $ids );
	}

}
