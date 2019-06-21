<?php

namespace Wikibase\Lexeme\Domain\EntityReferenceExtractors;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractor;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikimedia\Assert\Assert;

/**
 * Extracts the referenced entity ids of a lexeme's statements,
 * including those statements in sub-entities.
 * This will NOT include the ID of the item used for a lexeme language
 * (and anything else that is not in a statement).
 *
 * @license GPL-2.0-or-later
 */
class LexemeStatementEntityReferenceExtractor implements EntityReferenceExtractor {

	private $statementRefExtractor;
	private $formStatementRefExtractor;
	private $senseStatementRefExtractor;

	public function __construct(
		StatementEntityReferenceExtractor $statementRefExtractor,
		FormsStatementEntityReferenceExtractor $formStatementRefExtractor,
		SensesStatementEntityReferenceExtractor $senseStatementRefExtractor
	) {
		$this->statementRefExtractor = $statementRefExtractor;
		$this->formStatementRefExtractor = $formStatementRefExtractor;
		$this->senseStatementRefExtractor = $senseStatementRefExtractor;
	}

	/**
	 * @param EntityDocument $lexeme
	 * @return EntityId[]
	 */
	public function extractEntityIds( EntityDocument $lexeme ) {
		Assert::parameterType( Lexeme::class, $lexeme, '$lexeme' );
		'@phan-var Lexeme $lexeme';

		return array_merge(
			$this->statementRefExtractor->extractEntityIds( $lexeme ),
			$this->formStatementRefExtractor->extractEntityIds( $lexeme ),
			$this->senseStatementRefExtractor->extractEntityIds( $lexeme )
		);
	}

}
