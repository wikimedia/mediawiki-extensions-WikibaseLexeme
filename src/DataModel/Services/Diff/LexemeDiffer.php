<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityDifferStrategy;

class LexemeDiffer implements EntityDifferStrategy {

	public function canDiffEntityType( $entityType ) {
		// TODO: Implement canDiffEntityType() method.
	}

	public function diffEntities( EntityDocument $from, EntityDocument $to ) {
		// TODO: Implement diffEntities() method.
	}

	public function getConstructionDiff( EntityDocument $entity ) {
		// TODO: Implement getConstructionDiff() method.
	}

	public function getDestructionDiff( EntityDocument $entity ) {
		// TODO: Implement getDestructionDiff() method.
	}
}
