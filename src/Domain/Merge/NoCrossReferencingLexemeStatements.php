<?php

namespace Wikibase\Lexeme\Domain\Merge;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LexemeStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\LexemeSubEntityId;

/**
 * @license GPL-2.0-or-later
 */
class NoCrossReferencingLexemeStatements {

	/**
	 * @var array[] All violations from all validations of this objects life.
	 */
	private $violations = [];

	/**
	 * @var LexemeStatementEntityReferenceExtractor
	 */
	private $refExtractor;

	public function __construct( LexemeStatementEntityReferenceExtractor $refExtractor ) {
		$this->refExtractor = $refExtractor;
	}

	/**
	 * Validate the two Lexemes and collect the violations along with any violations from
	 * previous calls.
	 * @param Lexeme $one
	 * @param Lexeme $two
	 * @return bool true if valid
	 */
	public function validate( Lexeme $one, Lexeme $two ) {
		$oneId = $one->getId();
		$twoId = $two->getId();

		$oneRefIds = $this->refExtractor->extractEntityIds( $one );
		$twoRefIds = $this->refExtractor->extractEntityIds( $two );

		$this->collectViolations( $twoId, $twoRefIds, $oneId );
		$this->collectViolations( $oneId, $oneRefIds, $twoId );

		return $this->violations === [];
	}

	/**
	 * @param LexemeId $entityIdsFrom The LexemeId that the $entityIds references are from
	 * @param EntityId[] $entityIds The list of EntityIds that we are checking
	 * @param LexemeId $notToReference The LexemeId that when referenced will cause violations
	 */
	private function collectViolations(
		LexemeId $entityIdsFrom,
		array $entityIds,
		LexemeId $notToReference
	) {
		foreach ( $entityIds as $entityId ) {
			if (
				( $entityId instanceof LexemeId && $entityId->equals( $notToReference ) ) ||
				(
					$entityId instanceof LexemeSubEntityId &&
					$entityId->getLexemeId()->equals( $notToReference )
				)
			) {
				$this->violations[] = [ $entityIdsFrom, $entityId, $notToReference ];
			}
		}
	}

	/**
	 * Get violations of all validate() calls.
	 *
	 * @return array[] with each element containing:
	 * [ LexemeId $source, EntityId $reference, LexemeId $target ]
	 * Where:
	 *  - $source is the source Lexeme of the reference
	 *  - $reference is the reference to $target
	 *  - $target is the LexemeId being referenced
	 */
	public function getViolations() {
		return $this->violations;
	}

}
